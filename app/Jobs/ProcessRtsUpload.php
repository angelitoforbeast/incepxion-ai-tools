<?php

namespace App\Jobs;

use App\Exceptions\RtsFileInvalid;
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
            'status'       => 'scanning',
            'scanned_rows' => 0,
            'started_at'   => $upload->started_at ?? Carbon::now('Asia/Manila'),
        ])->save();

        $disk    = $upload->disk ?: 'local';
        $absPath = Storage::disk($disk)->path($upload->path);
        $ext     = strtolower(pathinfo($upload->path, PATHINFO_EXTENSION));

        try {
            // Called every ~1000 rows: publish scan progress AND allow cancellation.
            $parsed = $parser->parse($absPath, $ext, function (int $rowsRead) {
                RtsUpload::whereKey($this->uploadId)->update(['scanned_rows' => $rowsRead]);

                return $this->isCanceled();
            });
            $rows          = $parsed['rows'];       // [waybill => normalized row]
            $skippedCount  = $parsed['skipped_count'] ?? 0;
            $skippedSample = $parsed['skipped_sample'] ?? [];
            $upload->total_rows = count($rows);

            // Nothing usable — every row's required keys (Waybill Number / Order Status)
            // were blank or a formula. Fail cleanly with a user-safe message.
            if (count($rows) === 0) {
                $this->deleteSource($upload);
                $upload->forceFill([
                    'status'        => 'failed',
                    'user_message'  => $this->buildNoValidRowsMessage($skippedSample),
                    'error_message' => 'No valid rows after parse (required keys empty/formula).',
                    'finished_at'   => Carbon::now('Asia/Manila'),
                ])->save();

                return;
            }

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
                'status'       => 'done',
                'user_message' => $skippedCount > 0 ? $this->buildSkippedMessage($skippedCount, $skippedSample) : null,
                'finished_at'  => Carbon::now('Asia/Manila'),
            ])->save();
        } catch (RtsUploadCanceled $e) {
            // User stopped it — clean finish, no failed_jobs entry.
            $this->finalizeCanceled($upload);
        } catch (RtsFileInvalid $e) {
            // Clean, user-facing validation error (safe message — only required columns).
            $this->deleteSource($upload);
            $upload->forceFill([
                'status'        => 'failed',
                'user_message'  => $e->getMessage(),
                'error_message' => $e->getMessage(),
                'finished_at'   => Carbon::now('Asia/Manila'),
            ])->save();
        } catch (\Throwable $e) {
            // Unexpected/technical error — hide the details from the user, keep the full
            // detail for admins (error_message + the app error log).
            $this->deleteSource($upload);
            \Illuminate\Support\Facades\Log::error('RTS upload failed', [
                'upload' => $upload->id, 'user' => $upload->user_id, 'error' => $e->getMessage(),
            ]);
            $upload->forceFill([
                'status'        => 'failed',
                'user_message'  => 'Couldn’t process this file. Please make sure it’s a plain J&T export (no formulas) with the complete required columns, then try again.',
                'error_message' => mb_substr($e->getMessage(), 0, 2000),
                'finished_at'   => Carbon::now('Asia/Manila'),
            ])->save();

            throw $e;
        }
    }

    /** User-safe summary when some rows were skipped for invalid required data. */
    private function buildSkippedMessage(int $count, array $sample): string
    {
        return "Imported. Skipped {$count} row(s) with missing/invalid required data".$this->sampleSuffix($sample).'.';
    }

    /** User-safe message when no rows could be imported at all. */
    private function buildNoValidRowsMessage(array $sample): string
    {
        return 'No rows could be imported — the required Waybill Number / Order Status columns are empty or contain formulas'
            .$this->sampleSuffix($sample).'. Please upload a plain-values J&T export.';
    }

    private function sampleSuffix(array $sample): string
    {
        if (empty($sample)) {
            return '';
        }
        $parts = array_map(fn ($s) => 'row '.$s['row'].' ('.$s['field'].')', array_slice($sample, 0, 5));

        return ' — e.g. '.implode(', ', $parts);
    }

    /**
     * Called by the queue when the job permanently fails — including when the worker
     * is killed mid-run (e.g. a deploy restart → MaxAttemptsExceeded). Without this,
     * the upload would be stuck showing "scanning" forever.
     */
    public function failed(\Throwable $e): void
    {
        $upload = RtsUpload::find($this->uploadId);
        if (! $upload || in_array($upload->status, ['done', 'canceled', 'failed'], true)) {
            return;
        }

        $this->deleteSource($upload);
        $upload->forceFill([
            'status'        => $upload->canceled_at ? 'canceled' : 'failed',
            'error_message' => $upload->error_message ?: ('Processing stopped: '.mb_substr($e->getMessage(), 0, 300)),
            'finished_at'   => Carbon::now('Asia/Manila'),
        ])->save();
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
