<?php

namespace App\Jobs;

use App\Exceptions\RtsFileInvalid;
use App\Exceptions\RtsNeedsConfirmation;
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

    /**
     * Allow a couple of retries. A big export can be interrupted through no fault of the
     * file — a deploy restarting the worker, or the worker hitting its memory ceiling —
     * and previously that killed the upload outright ("attempted too many times").
     * The source file is kept until the final attempt so a retry can actually re-read it.
     */
    public int $tries = 3;

    /** Wait a few seconds between attempts (e.g. let a deploy finish). */
    public array $backoff = [10, 30];

    /** Rows buffered before a write. The SQL underneath is chunked smaller (INSERT_CHUNK). */
    private const BATCH_SIZE = 5000;

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

        // Running tallies, updated after every batch so the page shows real progress.
        $tally      = ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'total' => 0];
        $isFirst    = true;
        $conflictNo = 0;

        try {
            // Write each batch as the parser fills it, instead of holding the whole file in
            // memory and writing at the end. Memory stays flat and the counts climb live.
            $onBatch = function (array $batch) use ($upload, &$tally, &$isFirst, &$conflictNo) {
                if ($this->isCanceled()) {
                    throw new RtsUploadCanceled('Upload canceled by user.');
                }

                $existing = $this->existingStatuses($upload->user_id, array_keys($batch));

                // Only the FIRST batch can still stop the import — nothing is written yet.
                // A wrong/older export regresses consistently from its very first rows, so
                // this sample is what the whole-file scan used to tell us.
                if ($isFirst && ! $this->confirmed) {
                    $conflicts = $this->findConflicts($batch, $existing);
                    if (count($conflicts) > 0) {
                        throw new RtsNeedsConfirmation(
                            array_slice($conflicts, 0, self::CONFLICT_SAMPLE),
                            count($conflicts)
                        );
                    }
                }
                $isFirst = false;

                $conflictNo += count($this->findConflicts($batch, $existing));
                $this->writeBatch($upload, $batch, $existing, $tally);

                $upload->forceFill([
                    'status'         => 'processing',
                    'inserted'       => $tally['inserted'],
                    'updated'        => $tally['updated'],
                    'skipped'        => $tally['skipped'],
                    'processed_rows' => $tally['total'],
                    'conflict_count' => $conflictNo,
                ])->save();
            };

            // Called every ~1000 rows: publish scan progress AND allow cancellation.
            $parsed = $parser->parse($absPath, $ext, function (int $rowsRead) {
                RtsUpload::whereKey($this->uploadId)->update(['scanned_rows' => $rowsRead]);

                return $this->isCanceled();
            }, $onBatch, self::BATCH_SIZE);

            $skippedCount  = $parsed['skipped_count'] ?? 0;
            $skippedSample = $parsed['skipped_sample'] ?? [];

            // Whatever didn't fill a full batch.
            if (! empty($parsed['rows'])) {
                $onBatch($parsed['rows']);
            }

            // Nothing usable — every row's required keys (Waybill Number / Order Status)
            // were blank or a formula. Fail cleanly with a user-safe message.
            if ($tally['total'] === 0) {
                $this->deleteSource($upload);
                $upload->forceFill([
                    'status'        => 'failed',
                    'user_message'  => $this->buildNoValidRowsMessage($skippedSample),
                    'error_message' => 'No valid rows after parse (required keys empty/formula).',
                    'finished_at'   => Carbon::now('Asia/Manila'),
                ])->save();

                return;
            }

            $this->deleteSource($upload); // data is now in from_jnts

            $upload->forceFill([
                'status'       => 'done',
                'total_rows'   => $tally['total'],
                'user_message' => $skippedCount > 0 ? $this->buildSkippedMessage($skippedCount, $skippedSample) : null,
                'finished_at'  => Carbon::now('Asia/Manila'),
            ])->save();
        } catch (RtsNeedsConfirmation $e) {
            // Stopped before writing anything — wait for the user to Continue or Cancel.
            $upload->forceFill([
                'status'         => 'needs_confirmation',
                'conflict_count' => $e->conflictCount,
                'conflicts'      => $e->conflicts,
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
            \Illuminate\Support\Facades\Log::error('RTS upload failed', [
                'upload' => $upload->id, 'user' => $upload->user_id,
                'attempt' => $this->attempts(), 'error' => $e->getMessage(),
            ]);

            // Retries left: keep the source file and let the queue run it again.
            if ($this->attempts() < $this->tries) {
                throw $e;
            }

            $this->deleteSource($upload);
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
            // Interrupted (not a bad file) — say so plainly, without the queue internals.
            'user_message'  => $upload->canceled_at
                ? null
                : ($upload->user_message ?: 'Processing was interrupted before it could finish. Please upload the file again.'),
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

        // Rows are written as the file is read, so a cancel keeps whatever already landed.
        // Say so plainly rather than letting the user assume nothing was imported.
        $saved = (int) $upload->inserted + (int) $upload->updated + (int) $upload->skipped;

        $upload->forceFill([
            'status'       => 'canceled',
            'user_message' => $saved > 0
                ? 'Stopped. '.number_format($saved).' row(s) were already imported and were kept; the rest of the file was skipped.'
                : null,
            'canceled_at'  => $upload->canceled_at ?? Carbon::now('Asia/Manila'),
            'finished_at'  => Carbon::now('Asia/Manila'),
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
    /** Current statuses for these waybills, for THIS user only. */
    private function existingStatuses(int $userId, array $waybills): array
    {
        $existing = [];

        // Chunk the IN() lookup so a big batch can't blow past MySQL's placeholder limit.
        foreach (array_chunk($waybills, self::LOOKUP_CHUNK) as $chunk) {
            FromJnt::where('user_id', $userId)
                ->whereIn('waybill_number', $chunk)
                ->select('waybill_number', 'status')
                ->get()
                ->each(function ($r) use (&$existing) {
                    $existing[$r->waybill_number] = $r->status;
                });
        }

        return $existing;
    }

    /** Rows that would move a shipment backward (e.g. DELIVERED -> IN TRANSIT). */
    private function findConflicts(array $rows, array $existing): array
    {
        $conflicts = [];
        foreach ($rows as $wb => $r) {
            $old = $existing[$wb] ?? null;
            if ($old !== null && RtsStatus::isRegression($old, $r['status'])) {
                $conflicts[] = ['waybill' => $wb, 'from' => $old, 'to' => $r['status']];
            }
        }

        return $conflicts;
    }

    /**
     * Insert new waybills; update non-final existing ones. FINAL rows (delivered/returned)
     * are never touched — regressions are skipped, preserving the final status.
     */
    private function writeBatch(RtsUpload $upload, array $rows, array $existing, array &$tally): void
    {
        $now = Carbon::now('Asia/Manila')->format('Y-m-d H:i:s');

        $toInsert   = [];
        $updWithRts = []; // update status + signingtime + rts_reason
        $updNoRts   = []; // update status + signingtime only

        foreach ($rows as $wb => $r) {
            if (isset($existing[$wb])) {
                if (RtsStatus::isFinal($existing[$wb])) {
                    $tally['skipped']++; // never downgrade a finalized shipment
                    $tally['total']++;
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
                $tally['updated']++;
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
                $tally['inserted']++;
            }
            $tally['total']++;
        }

        // One transaction per batch: a batch either lands whole or not at all. The SQL is
        // split further because a 5k batch would exceed MySQL's ~65k placeholder ceiling.
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
    }
}
