<?php

namespace App\Jobs;

use App\Exceptions\RtsUploadCanceled;
use App\Models\FromJnt;
use App\Models\RtsUpload;
use App\Services\RtsFileParser;
use App\Services\RtsStatus;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProcessRtsUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1200; // 20 min
    public int $tries = 1;

    private const INSERT_CHUNK = 1000;
    private const LOOKUP_CHUNK = 3000;
    private const CONFLICT_SAMPLE = 25;

    public function __construct(public int $uploadId, public bool $confirmed = false)
    {
        $this->onQueue('rts'); // dedicated worker
    }

    public function handle(RtsFileParser $parser): void
    {
        $upload = RtsUpload::find($this->uploadId);
        if (! $upload || in_array($upload->status, ['done', 'canceled'], true)) {
            return;
        }

        // Canceled before we even started — clean up and stop.
        if ($this->isCanceled()) {
            $this->finalizeCanceled($upload);

            return;
        }

        $upload->forceFill([
            'status'     => 'scanning',
            'started_at' => $upload->started_at ?? Carbon::now('Asia/Manila'),
        ])->save();

        $disk    = $upload->disk ?: 'local';
        $absPath = Storage::disk($disk)->path($upload->path);
        $ext     = strtolower(pathinfo($upload->path, PATHINFO_EXTENSION));

        try {
            // The parser polls this every ~2000 rows so a long scan can be stopped.
            $parsed = $parser->parse($absPath, $ext, fn () => $this->isCanceled());
            $rows   = $parsed['rows'];              // [waybill => normalized row]
            $upload->total_rows = count($rows);

            $waybills = array_keys($rows);

            // Existing statuses for THIS user only. Chunk the IN() lookup so huge files
            // don't blow past MySQL's prepared-statement placeholder limit (~65k).
            $existing = [];
            foreach (array_chunk($waybills, self::LOOKUP_CHUNK) as $chunk) {
                FromJnt::where('user_id', $upload->user_id)
                    ->whereIn('waybill_number', $chunk)
                    ->select('waybill_number', 'status')
                    ->get()
                    ->each(function ($r) use (&$existing) {
                        $existing[$r->waybill_number] = $r->status;
                    });
            }

            // Detect regressive rows (possible wrong file).
            $conflicts = [];
            foreach ($rows as $wb => $r) {
                $old = $existing[$wb] ?? null;
                if ($old !== null && RtsStatus::isRegression($old, $r['status'])) {
                    $conflicts[] = ['waybill' => $wb, 'from' => $old, 'to' => $r['status']];
                }
            }

            if (count($conflicts) > 0 && ! $this->confirmed) {
                $upload->forceFill([
                    'status'         => 'needs_confirmation',
                    'conflict_count' => count($conflicts),
                    'conflicts'      => array_slice($conflicts, 0, self::CONFLICT_SAMPLE),
                ])->save();

                return; // Wait for the user to Continue or Cancel.
            }

            // Last chance to bail before writing anything to from_jnts.
            if ($this->isCanceled()) {
                throw new RtsUploadCanceled('Upload canceled by user.');
            }

            $upload->forceFill(['status' => 'processing', 'conflict_count' => count($conflicts)])->save();

            $this->apply($upload, $rows, $existing);

            $this->deleteSource($upload); // data is now in from_jnts

            $upload->forceFill([
                'status'      => 'done',
                'finished_at' => Carbon::now('Asia/Manila'),
            ])->save();
        } catch (RtsUploadCanceled $e) {
            // User stopped it — clean finish, no failed_jobs entry.
            $this->finalizeCanceled($upload);
        } catch (\Throwable $e) {
            $this->deleteSource($upload); // auto-clean even on failure (no retries)

            $upload->forceFill([
                'status'        => 'failed',
                'error_message' => mb_substr($e->getMessage(), 0, 1000),
                'finished_at'   => Carbon::now('Asia/Manila'),
            ])->save();

            throw $e;
        }
    }

    /** True if the user requested cancellation while the job is running. */
    private function isCanceled(): bool
    {
        return RtsUpload::whereKey($this->uploadId)->whereNotNull('canceled_at')->exists();
    }

    /** Discard the source file and mark the upload canceled. */
    private function finalizeCanceled(RtsUpload $upload): void
    {
        $this->deleteSource($upload);

        $upload->forceFill([
            'status'      => 'canceled',
            'canceled_at' => $upload->canceled_at ?? Carbon::now('Asia/Manila'),
            'finished_at' => Carbon::now('Asia/Manila'),
        ])->save();
    }

    /** Remove the raw uploaded file from storage (best-effort). */
    private function deleteSource(RtsUpload $upload): void
    {
        try {
            $disk = $upload->disk ?: 'local';
            if ($upload->path && Storage::disk($disk)->exists($upload->path)) {
                Storage::disk($disk)->delete($upload->path);
            }
        } catch (\Throwable $e) {
            // non-fatal
        }
    }

    /**
     * Insert new waybills; update non-final existing ones. FINAL rows (delivered/returned)
     * are never touched — regressions are skipped, preserving the final status.
     */
    private function apply(RtsUpload $upload, array $rows, $existing): void
    {
        $now = Carbon::now('Asia/Manila')->format('Y-m-d H:i:s');

        $toInsert       = [];
        $updWithRts     = []; // update status + signingtime + rts_reason
        $updNoRts       = []; // update status + signingtime only
        $inserted = 0; $updated = 0; $skipped = 0;

        foreach ($rows as $wb => $r) {
            if (isset($existing[$wb])) {
                if (RtsStatus::isFinal($existing[$wb])) {
                    $skipped++; // never downgrade a finalized shipment
                    continue;
                }

                $base = [
                    'user_id'        => $upload->user_id,
                    'waybill_number' => $wb,
                    'status'         => $r['status'],
                    'signingtime'    => $r['signingtime'],
                    'updated_at'     => $now,
                ];

                if (trim((string) ($r['rts_reason'] ?? '')) !== '') {
                    $base['rts_reason'] = $r['rts_reason'];
                    $updWithRts[] = $base;
                } else {
                    $updNoRts[] = $base;
                }
                $updated++;
            } else {
                $toInsert[] = [
                    'user_id'             => $upload->user_id,
                    'waybill_number'      => $wb,
                    'sender'              => $r['sender'],
                    'cod'                 => $r['cod'],
                    'status'              => $r['status'],
                    'item_name'           => $r['item_name'],
                    'submission_time'     => $r['submission_time'],
                    'receiver'            => $r['receiver'],
                    'receiver_cellphone'  => $r['receiver_cellphone'],
                    'signingtime'         => $r['signingtime'],
                    'remarks'             => $r['remarks'],
                    'province'            => $r['province'],
                    'city'                => $r['city'],
                    'barangay'            => $r['barangay'],
                    'total_shipping_cost' => $r['total_shipping_cost'],
                    'rts_reason'          => $r['rts_reason'],
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ];
                $inserted++;
            }
        }

        DB::transaction(function () use ($toInsert, $updWithRts, $updNoRts) {
            foreach (array_chunk($toInsert, self::INSERT_CHUNK) as $chunk) {
                FromJnt::insert($chunk);
            }
            foreach (array_chunk($updWithRts, self::INSERT_CHUNK) as $chunk) {
                FromJnt::upsert($chunk, ['user_id', 'waybill_number'], ['status', 'signingtime', 'rts_reason', 'updated_at']);
            }
            foreach (array_chunk($updNoRts, self::INSERT_CHUNK) as $chunk) {
                FromJnt::upsert($chunk, ['user_id', 'waybill_number'], ['status', 'signingtime', 'updated_at']);
            }
        });

        $upload->forceFill([
            'processed_rows' => $inserted + $updated + $skipped,
            'inserted'       => $inserted,
            'updated'        => $updated,
            'skipped'        => $skipped,
        ])->save();
    }
}
