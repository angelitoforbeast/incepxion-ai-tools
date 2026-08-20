<?php

namespace Tests\Feature;

use App\Jobs\ProcessRtsUpload;
use App\Livewire\RtsMonitor;
use App\Livewire\RtsProcessor;
use App\Models\FromJnt;
use App\Models\RtsUpload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class RtsProcessorTest extends TestCase
{
    use RefreshDatabase;

    private function makeUpload(User $user, string $csv): RtsUpload
    {
        Storage::fake('local');
        $path = 'uploads/rts/test.csv';
        Storage::disk('local')->put($path, $csv);

        return RtsUpload::create([
            'user_id'       => $user->id,
            'original_name' => 'test.csv',
            'disk'          => 'local',
            'path'          => $path,
            'status'        => 'queued',
        ]);
    }

    /** Build a J&T CSV with the exact, complete required headers. Each row: [waybill, status, opts…]. */
    private function jntCsv(array $rows): string
    {
        $lines = ['Waybill Number,Order Status,Item Name,Sender Name,Cod,Submission Time,SigningTime,Total Shipping Cost'];
        foreach ($rows as $r) {
            $lines[] = implode(',', [
                $r['waybill']    ?? '',
                $r['status']     ?? '',
                $r['item']       ?? '1 x LIP',
                $r['sender']     ?? 'ShopA',
                $r['cod']        ?? '0',
                $r['submission'] ?? '2026-07-05 09:00:00',
                $r['signing']    ?? '',
                $r['ship']       ?? '0',
            ]);
        }

        return implode("\n", $lines)."\n";
    }

    public function test_regression_pauses_for_confirmation_then_skips_on_continue(): void
    {
        $user = User::factory()->create();

        // Existing shipments: WB1 already delivered (final), WB3 still in transit.
        FromJnt::insert([
            ['user_id' => $user->id, 'waybill_number' => 'WB1', 'status' => 'Delivered',  'submission_time' => '2026-07-01 08:00:00', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $user->id, 'waybill_number' => 'WB3', 'status' => 'In Transit', 'submission_time' => '2026-07-02 08:00:00', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // File tries to move WB1 backward (delivered → in transit) = regression.
        $csv = $this->jntCsv([
            ['waybill' => 'WB1', 'status' => 'In Transit', 'submission' => '2026-07-01 08:00:00'],
            ['waybill' => 'WB2', 'status' => 'In Transit', 'submission' => '2026-07-05 09:00:00'],
            ['waybill' => 'WB3', 'status' => 'Delivered',  'submission' => '2026-07-02 08:00:00'],
        ]);

        $upload = $this->makeUpload($user, $csv);

        // First pass: should detect the regression and pause.
        ProcessRtsUpload::dispatchSync($upload->id);
        $upload->refresh();

        $this->assertSame('needs_confirmation', $upload->status);
        $this->assertSame(1, $upload->conflict_count);
        // Nothing may be written while we're still asking — WB2 is new and must not exist yet.
        $this->assertFalse(FromJnt::where('waybill_number', 'WB2')->exists());

        // User continues: apply, skipping backward rows.
        $upload->update(['status' => 'processing']);
        ProcessRtsUpload::dispatchSync($upload->id, true);
        $upload->refresh();

        $this->assertSame('done', $upload->status);
        $this->assertSame(1, $upload->inserted); // WB2
        $this->assertSame(1, $upload->updated);  // WB3
        $this->assertSame(1, $upload->skipped);  // WB1 (final, preserved)

        // WB1 stays Delivered (not downgraded), WB3 moves forward, WB2 created.
        $this->assertSame('Delivered', FromJnt::where('waybill_number', 'WB1')->value('status'));
        $this->assertSame('Delivered', FromJnt::where('waybill_number', 'WB3')->value('status'));
        $this->assertSame('In Transit', FromJnt::where('waybill_number', 'WB2')->value('status'));
    }

    public function test_clean_file_processes_without_confirmation(): void
    {
        $user = User::factory()->create();

        $csv = $this->jntCsv([
            ['waybill' => 'WBA', 'status' => 'In Transit'],
            ['waybill' => 'WBB', 'status' => 'Delivered'],
        ]);

        $upload = $this->makeUpload($user, $csv);
        ProcessRtsUpload::dispatchSync($upload->id);
        $upload->refresh();

        $this->assertSame('done', $upload->status);
        $this->assertSame(2, $upload->inserted);
        $this->assertSame(0, $upload->conflict_count);
    }

    public function test_wrong_file_fails_clearly(): void
    {
        $user = User::factory()->create();
        // Missing the required J&T columns → must be rejected before processing.
        $csv = "Waybill Number,Order Status\nWB1,Delivered\n";

        $upload = $this->makeUpload($user, $csv);

        try {
            ProcessRtsUpload::dispatchSync($upload->id);
        } catch (\Throwable $e) {
            // job rethrows after marking failed
        }

        $upload->refresh();
        $this->assertSame('failed', $upload->status);
        $this->assertStringContainsString('missing', (string) $upload->error_message);
        // Source file is auto-deleted even on failure.
        Storage::disk('local')->assertMissing($upload->path);
    }

    public function test_source_file_deleted_after_success(): void
    {
        $user = User::factory()->create();
        $csv = $this->jntCsv([['waybill' => 'WBZ', 'status' => 'In Transit']]);
        $upload = $this->makeUpload($user, $csv);

        ProcessRtsUpload::dispatchSync($upload->id);
        $upload->refresh();

        $this->assertSame('done', $upload->status);
        Storage::disk('local')->assertMissing($upload->path);
    }

    public function test_formula_in_required_column_fails_cleanly_no_crash(): void
    {
        $user = User::factory()->create();
        // Order Status is a formula → cleared to empty → every row skipped. Must NOT crash,
        // and the user message must be clean (no SQL), mentioning the required column.
        $csv = "Waybill Number,Order Status,Item Name,Sender Name,Cod,Submission Time,SigningTime,Total Shipping Cost\n"
            ."WB1,=A1,ITEM,ShopA,100,2026-07-01 08:00:00,2026-07-02 08:00:00,50\n"
            ."WB2,=B2,ITEM,ShopA,100,2026-07-01 08:00:00,2026-07-02 08:00:00,50\n";

        $upload = $this->makeUpload($user, $csv);
        ProcessRtsUpload::dispatchSync($upload->id);
        $fresh = $upload->fresh();

        $this->assertSame('failed', $fresh->status);
        $this->assertStringContainsString('Order Status', (string) $fresh->user_message);
        $this->assertStringNotContainsString('SQLSTATE', (string) $fresh->user_message);
        $this->assertSame(0, FromJnt::where('user_id', $user->id)->count());
    }

    public function test_oversized_value_is_truncated_not_crashed(): void
    {
        $user = User::factory()->create();
        $long = str_repeat('X', 400); // > 255 → would overflow without truncation
        $csv = "Waybill Number,Order Status,Item Name,Sender Name,Cod,Submission Time,SigningTime,Total Shipping Cost\n"
            ."WB1,Delivered,{$long},ShopA,100,2026-07-01 08:00:00,2026-07-02 08:00:00,50\n";

        $upload = $this->makeUpload($user, $csv);
        ProcessRtsUpload::dispatchSync($upload->id);

        $this->assertSame('done', $upload->fresh()->status);
        $item = FromJnt::where('user_id', $user->id)->value('item_name');
        $this->assertLessThanOrEqual(255, mb_strlen((string) $item));
    }

    public function test_skipped_rows_are_reported_on_success(): void
    {
        $user = User::factory()->create();
        $csv = $this->jntCsv([
            ['waybill' => 'WB1', 'status' => 'Delivered'],
            ['waybill' => 'WB2', 'status' => ''], // empty required status → skipped
        ]);

        $upload = $this->makeUpload($user, $csv);
        ProcessRtsUpload::dispatchSync($upload->id);
        $fresh = $upload->fresh();

        $this->assertSame('done', $fresh->status);
        $this->assertSame(1, $fresh->inserted);
        $this->assertStringContainsString('Skipped 1', (string) $fresh->user_message);
    }

    public function test_scan_progress_is_reported(): void
    {
        $user = User::factory()->create();
        $rows = [];
        for ($i = 0; $i < 1100; $i++) {
            $rows[] = ['waybill' => "WB{$i}", 'status' => 'In Transit'];
        }
        $upload = $this->makeUpload($user, $this->jntCsv($rows));

        ProcessRtsUpload::dispatchSync($upload->id);
        $fresh = $upload->fresh();

        $this->assertSame('done', $fresh->status);
        // The parser reports progress every 1000 rows, so scanned_rows advances past 0.
        $this->assertGreaterThanOrEqual(1000, $fresh->scanned_rows);
    }

    public function test_exact_matching_ignores_lookalike_columns(): void
    {
        $user = User::factory()->create();
        // Trap columns present: "Sender City/Address" and "Receipt Waybill No" must NOT be
        // grabbed for `sender` / `waybill_number` — only the exact headers count.
        $csv = "Waybill Number,Order Status,Item Name,Sender Name,Cod,Submission Time,SigningTime,Total Shipping Cost,Sender City,Sender Address,Receipt Waybill No\n"
            ."WB1,Delivered,ITEM,ShopReal,100,2026-07-01 08:00:00,2026-07-02 08:00:00,50,Manila,123 Real St,RCPT-999\n";

        $upload = $this->makeUpload($user, $csv);
        ProcessRtsUpload::dispatchSync($upload->id);

        $row = FromJnt::where('user_id', $user->id)->first();
        $this->assertNotNull($row);
        $this->assertSame('WB1', $row->waybill_number);   // not RCPT-999
        $this->assertSame('ShopReal', $row->sender);      // not Manila / 123 Real St
    }

    public function test_missing_required_column_is_rejected_with_name(): void
    {
        $user = User::factory()->create();
        // Missing "Total Shipping Cost".
        $csv = "Waybill Number,Order Status,Item Name,Sender Name,Cod,Submission Time,SigningTime\n"
            ."WB1,Delivered,ITEM,ShopA,100,2026-07-01 08:00:00,2026-07-02 08:00:00\n";

        $upload = $this->makeUpload($user, $csv);
        try {
            ProcessRtsUpload::dispatchSync($upload->id);
        } catch (\Throwable $e) {
            // rethrown after marking failed
        }

        $upload->refresh();
        $this->assertSame('failed', $upload->status);
        $this->assertStringContainsString('Total Shipping Cost', (string) $upload->error_message);
    }

    /** Write a real .xlsx whose sheets are [name => rows-of-cells]. */
    private function makeXlsxUpload(User $user, array $sheets): RtsUpload
    {
        Storage::fake('local');
        $abs = tempnam(sys_get_temp_dir(), 'rts').'.xlsx';

        $writer = new \OpenSpout\Writer\XLSX\Writer();
        $writer->openToFile($abs);
        $first = true;
        foreach ($sheets as $name => $rows) {
            if (! $first) {
                $writer->addNewSheetAndMakeItCurrent();
            }
            $writer->getCurrentSheet()->setName($name);
            foreach ($rows as $cells) {
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues($cells));
            }
            $first = false;
        }
        $writer->close();

        $path = 'uploads/rts/test.xlsx';
        Storage::disk('local')->put($path, file_get_contents($abs));
        @unlink($abs);

        return RtsUpload::create([
            'user_id' => $user->id, 'original_name' => 'test.xlsx',
            'disk' => 'local', 'path' => $path, 'status' => 'queued',
        ]);
    }

    private const JNT_HEADERS = [
        'Waybill Number', 'Order Status', 'Item Name', 'Sender Name',
        'Cod', 'Submission Time', 'SigningTime', 'Total Shipping Cost',
    ];

    public function test_only_the_matching_sheet_is_imported(): void
    {
        $user = User::factory()->create();

        $upload = $this->makeXlsxUpload($user, [
            // A helper sheet that is NOT the J&T export — must be ignored entirely.
            'Helper' => [
                ['Shop', 'Notes'],
                ['SHOP 1', 'ignore me'],
                ['SHOP 2', 'ignore me too'],
            ],
            'Export' => [
                self::JNT_HEADERS,
                ['WB1', 'Delivered', 'ITEM', 'ShopA', '100', '2026-07-01 08:00:00', '2026-07-02 08:00:00', '50'],
                ['WB2', 'In Transit', 'ITEM', 'ShopA', '100', '2026-07-03 08:00:00', '', '50'],
            ],
        ]);

        ProcessRtsUpload::dispatchSync($upload->id);

        $upload->refresh();
        $this->assertSame('done', $upload->status);
        $this->assertSame(2, FromJnt::where('user_id', $user->id)->count());
        $this->assertSame(2, $upload->inserted);
        // The helper sheet's rows never became skipped rows — it was skipped as a whole.
        $this->assertSame(0, $upload->skipped);
        $this->assertNull($upload->user_message);
    }

    public function test_sheets_after_the_data_sheet_are_ignored(): void
    {
        $user = User::factory()->create();

        $upload = $this->makeXlsxUpload($user, [
            'Export' => [
                self::JNT_HEADERS,
                ['WB1', 'Delivered', 'ITEM', 'ShopA', '100', '2026-07-01 08:00:00', '2026-07-02 08:00:00', '50'],
            ],
            // A second copy of the data must NOT be read again (this is what inflated the
            // scanned-row count to ~3x the file's real size).
            'Copy' => [
                self::JNT_HEADERS,
                ['WB9', 'Delivered', 'ITEM', 'ShopA', '100', '2026-07-04 08:00:00', '2026-07-05 08:00:00', '50'],
            ],
        ]);

        ProcessRtsUpload::dispatchSync($upload->id);

        $upload->refresh();
        $this->assertSame('done', $upload->status);
        $this->assertSame(1, FromJnt::where('user_id', $user->id)->count());
        $this->assertTrue(FromJnt::where('waybill_number', 'WB1')->exists());
        $this->assertFalse(FromJnt::where('waybill_number', 'WB9')->exists());
    }

    public function test_formula_mirror_sheet_is_skipped_for_the_real_data_sheet(): void
    {
        $user = User::factory()->create();

        $upload = $this->makeXlsxUpload($user, [
            // Same headers as the export, but every required cell is a formula pointing at
            // the real sheet — it reads back blank, so it must not be chosen.
            'View' => array_merge([self::JNT_HEADERS], array_map(fn ($i) => [
                "=Data!A{$i}", "=Data!B{$i}", '=Data!C'.$i, 'ShopA', '100',
                '2026-07-01 08:00:00', '', '50',
            ], range(2, 60))),
            'Data' => [
                self::JNT_HEADERS,
                ['WB1', 'Delivered', 'ITEM', 'ShopA', '100', '2026-07-01 08:00:00', '2026-07-02 08:00:00', '50'],
                ['WB2', 'In Transit', 'ITEM', 'ShopA', '100', '2026-07-03 08:00:00', '', '50'],
            ],
        ]);

        ProcessRtsUpload::dispatchSync($upload->id);

        $upload->refresh();
        $this->assertSame('done', $upload->status);
        $this->assertSame(2, $upload->inserted);
        $this->assertSame('Delivered', FromJnt::where('waybill_number', 'WB1')->value('status'));
        $this->assertSame('In Transit', FromJnt::where('waybill_number', 'WB2')->value('status'));
    }

    public function test_xlsx_with_no_matching_sheet_is_rejected_with_column_names(): void
    {
        $user = User::factory()->create();

        $upload = $this->makeXlsxUpload($user, [
            'One' => [['Shop', 'Notes'], ['SHOP 1', 'x']],
            'Two' => [['A', 'B'], ['1', '2']],
        ]);

        try {
            ProcessRtsUpload::dispatchSync($upload->id);
        } catch (\Throwable $e) {
            // rethrown after marking failed
        }

        $upload->refresh();
        $this->assertSame('failed', $upload->status);
        $this->assertStringContainsString('Waybill Number', (string) $upload->user_message);
    }

    public function test_file_larger_than_one_batch_is_written_in_batches(): void
    {
        $user = User::factory()->create();

        // 6,000 rows spans two batches (5,000 + 1,000) — the whole point of streaming.
        $rows = [];
        for ($i = 1; $i <= 6000; $i++) {
            $rows[] = ['waybill' => 'WB'.$i, 'status' => 'In Transit'];
        }

        $upload = $this->makeUpload($user, $this->jntCsv($rows));
        ProcessRtsUpload::dispatchSync($upload->id);
        $upload->refresh();

        $this->assertSame('done', $upload->status);
        $this->assertSame(6000, $upload->inserted);
        $this->assertSame(6000, FromJnt::where('user_id', $user->id)->count());
        // First and last rows both landed, so no batch was dropped in between.
        $this->assertTrue(FromJnt::where('waybill_number', 'WB1')->exists());
        $this->assertTrue(FromJnt::where('waybill_number', 'WB6000')->exists());
    }

    public function test_second_batch_updates_rows_written_by_the_first(): void
    {
        $user = User::factory()->create();

        // The same waybill appears in batch 1 and again in batch 2; the later row wins.
        $rows = [['waybill' => 'DUP', 'status' => 'In Transit']];
        for ($i = 1; $i <= 5200; $i++) {
            $rows[] = ['waybill' => 'WB'.$i, 'status' => 'In Transit'];
        }
        $rows[] = ['waybill' => 'DUP', 'status' => 'Delivered'];

        $upload = $this->makeUpload($user, $this->jntCsv($rows));
        ProcessRtsUpload::dispatchSync($upload->id);
        $upload->refresh();

        $this->assertSame('done', $upload->status);
        $this->assertSame('Delivered', FromJnt::where('waybill_number', 'DUP')->value('status'));
        $this->assertSame(1, FromJnt::where('waybill_number', 'DUP')->count());
    }

    public function test_monitor_classifies_strictly_by_status(): void
    {
        $user = User::factory()->create();
        $now = '2026-07-10 08:00:00';
        FromJnt::insert([
            ['user_id' => $user->id, 'waybill_number' => 'D1', 'status' => 'Delivered',  'submission_time' => $now, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $user->id, 'waybill_number' => 'D2', 'status' => 'Delivering', 'submission_time' => $now, 'created_at' => now(), 'updated_at' => now()], // NOT delivered
            ['user_id' => $user->id, 'waybill_number' => 'R1', 'status' => 'For Return', 'submission_time' => $now, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $user->id, 'waybill_number' => 'R2', 'status' => 'Returned',   'submission_time' => $now, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $user->id, 'waybill_number' => 'T1', 'status' => 'In Transit', 'submission_time' => $now, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Livewire::actingAs($user)->test(RtsMonitor::class)
            ->set('from', '2026-07-01')
            ->set('to', '2026-07-31')
            ->assertViewHas('full', fn ($f) => $f['total'] === 5
                && $f['totalDelivered'] === 1   // only "Delivered", not "Delivering"
                && $f['totalRts'] === 2         // "For Return" + "Returned"
                && $f['totalTransit'] === 2);   // "Delivering" + "In Transit"
    }

    public function test_projection_uses_partial_date_window(): void
    {
        $user = User::factory()->create();
        FromJnt::insert([
            // Early cohort (already settled)
            ['user_id' => $user->id, 'waybill_number' => 'E1', 'status' => 'Returned',   'submission_time' => '2026-07-02 08:00:00', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $user->id, 'waybill_number' => 'E2', 'status' => 'Delivered',   'submission_time' => '2026-07-03 08:00:00', 'created_at' => now(), 'updated_at' => now()],
            // Late cohort (still moving)
            ['user_id' => $user->id, 'waybill_number' => 'L1', 'status' => 'In Transit',  'submission_time' => '2026-07-19 08:00:00', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $user->id, 'waybill_number' => 'L2', 'status' => 'In Transit',  'submission_time' => '2026-07-20 08:00:00', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Livewire::actingAs($user)->test(RtsMonitor::class)
            ->set('from', '2026-07-01')
            ->set('to', '2026-07-21')
            ->assertViewHas('full', fn ($f) => $f['total'] === 4)
            ->set('partialDays', 5) // project only Jul 1 → Jul 6 (early cohort only)
            ->assertViewHas('projection', fn ($p) => $p['total'] === 2
                && $p['totalRts'] === 1
                && $p['totalDelivered'] === 1
                && $p['totalTransit'] === 0);
    }

    public function test_projection_defaults_to_all_when_below_threshold(): void
    {
        $user = User::factory()->create();
        FromJnt::insert([
            ['user_id' => $user->id, 'waybill_number' => 'X1', 'status' => 'Delivered', 'submission_time' => '2026-07-02 08:00:00', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $user->id, 'waybill_number' => 'X2', 'status' => 'Returned',  'submission_time' => '2026-07-20 08:00:00', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Fewer than 300 shipments → default projection window = entire range → projection == full.
        Livewire::actingAs($user)->test(RtsMonitor::class)
            ->set('from', '2026-07-01')
            ->set('to', '2026-07-21')
            ->assertViewHas('projection', fn ($p) => $p['total'] === 2)
            ->assertViewHas('full', fn ($f) => $f['total'] === 2);
    }

    public function test_monitor_multi_select_item_filter(): void
    {
        $user = User::factory()->create();
        $now = '2026-07-10 08:00:00';
        FromJnt::insert([
            ['user_id' => $user->id, 'waybill_number' => 'A1', 'item_name' => 'Lip Tattoo', 'status' => 'Returned',  'submission_time' => $now, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $user->id, 'waybill_number' => 'B1', 'item_name' => 'Face Wash',  'status' => 'Delivered', 'submission_time' => $now, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Livewire::actingAs($user)->test(RtsMonitor::class)
            ->set('from', '2026-07-01')
            ->set('to', '2026-07-31')
            ->assertViewHas('full', fn ($f) => $f['total'] === 2)   // both items
            ->set('selectedItems', ['Lip Tattoo'])                 // filter to one item
            ->assertViewHas('full', fn ($f) => $f['total'] === 1 && $f['totalRts'] === 1 && $f['totalDelivered'] === 0);
    }

    public function test_projection_default_recomputes_when_filter_changes(): void
    {
        $user = User::factory()->create();
        FromJnt::insert([
            ['user_id' => $user->id, 'waybill_number' => 'A1', 'item_name' => 'ITEM A', 'status' => 'Delivered', 'submission_time' => '2026-07-02 08:00:00', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $user->id, 'waybill_number' => 'A2', 'item_name' => 'ITEM A', 'status' => 'Returned',  'submission_time' => '2026-07-20 08:00:00', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $user->id, 'waybill_number' => 'B1', 'item_name' => 'ITEM B', 'status' => 'Delivered', 'submission_time' => '2026-07-02 08:00:00', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Livewire::actingAs($user)->test(RtsMonitor::class)
            ->set('from', '2026-07-01')
            ->set('to', '2026-07-21')
            ->set('partialDays', 1)                 // simulate a stale, tiny projection window
            ->set('selectedItems', ['ITEM A'])      // filter change must recompute the default
            // Filtered set (2 rows) < 300 → projection uses ALL available, not the stale 1-day window.
            ->assertViewHas('projection', fn ($p) => $p['total'] === 2);
    }

    public function test_monitor_remove_filter_chip(): void
    {
        $user = User::factory()->create();
        $now = '2026-07-10 08:00:00';
        FromJnt::insert([
            ['user_id' => $user->id, 'waybill_number' => 'A1', 'item_name' => 'Lip Tattoo', 'status' => 'Returned',  'submission_time' => $now, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $user->id, 'waybill_number' => 'B1', 'item_name' => 'Face Wash',  'status' => 'Delivered', 'submission_time' => $now, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Livewire::actingAs($user)->test(RtsMonitor::class)
            ->set('from', '2026-07-01')
            ->set('to', '2026-07-31')
            ->set('selectedItems', ['Lip Tattoo', 'Face Wash'])
            ->assertViewHas('full', fn ($f) => $f['total'] === 2)
            ->call('removeFilter', 'item', 0)          // remove "Lip Tattoo" (index 0)
            ->assertSet('selectedItems', ['Face Wash'])
            ->assertViewHas('full', fn ($f) => $f['total'] === 1);
    }

    public function test_monitor_filter_options_cascade(): void
    {
        $user = User::factory()->create();
        $now = '2026-07-10 08:00:00';
        FromJnt::insert([
            ['user_id' => $user->id, 'waybill_number' => 'A1', 'item_name' => 'Lip Tattoo', 'sender' => 'ShopA', 'status' => 'Returned',  'submission_time' => $now, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $user->id, 'waybill_number' => 'B1', 'item_name' => 'Face Wash',  'sender' => 'ShopB', 'status' => 'Delivered', 'submission_time' => $now, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Livewire::actingAs($user)->test(RtsMonitor::class)
            ->set('from', '2026-07-01')
            ->set('to', '2026-07-31')
            ->assertViewHas('senderOptions', ['ShopA', 'ShopB'])          // all senders shown
            ->set('selectedItems', ['Lip Tattoo'])
            ->assertViewHas('senderOptions', ['ShopA'])                   // cascaded: only ShopA carries Lip Tattoo
            ->assertViewHas('itemOptions', ['Face Wash', 'Lip Tattoo']);  // item list keeps all (skips its own filter)
    }

    public function test_scoping_is_per_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // Same waybill exists for user B as delivered — must NOT affect user A's import.
        FromJnt::insert([['user_id' => $userB->id, 'waybill_number' => 'WBX', 'status' => 'Delivered', 'submission_time' => '2026-07-01 08:00:00', 'created_at' => now(), 'updated_at' => now()]]);

        $csv = $this->jntCsv([['waybill' => 'WBX', 'status' => 'In Transit']]);
        $upload = $this->makeUpload($userA, $csv);
        ProcessRtsUpload::dispatchSync($upload->id);
        $upload->refresh();

        // No conflict for user A (their WBX is new), and B's row is untouched.
        $this->assertSame('done', $upload->status);
        $this->assertSame(1, $upload->inserted);
        $this->assertSame('In Transit', FromJnt::where('user_id', $userA->id)->where('waybill_number', 'WBX')->value('status'));
        $this->assertSame('Delivered', FromJnt::where('user_id', $userB->id)->where('waybill_number', 'WBX')->value('status'));
    }

    public function test_canceling_before_processing_imports_nothing(): void
    {
        $user = User::factory()->create();
        $csv = "Waybill Number,Status,Submission Time\nWB1,In Transit,2026-07-05 09:00:00\n";
        $upload = $this->makeUpload($user, $csv);

        // User hit Cancel while it was still queued.
        $upload->update(['canceled_at' => now()]);

        ProcessRtsUpload::dispatchSync($upload->id);
        $upload->refresh();

        $this->assertSame('canceled', $upload->status);
        $this->assertSame(0, FromJnt::where('user_id', $user->id)->count());
    }

    public function test_cancel_button_finalizes_a_queued_upload(): void
    {
        $user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);
        $csv = "Waybill Number,Status,Submission Time\nWB1,In Transit,2026-07-05 09:00:00\n";
        $upload = $this->makeUpload($user, $csv);

        Livewire::actingAs($user)->test(RtsProcessor::class)
            ->set('currentUploadId', $upload->id)
            ->call('cancelUpload');

        $fresh = $upload->fresh();
        $this->assertSame('canceled', $fresh->status);
        $this->assertNotNull($fresh->canceled_at);
    }

    public function test_cancel_finalizes_a_scanning_upload_even_if_the_job_is_gone(): void
    {
        $user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);
        $csv = "Waybill Number,Status,Submission Time\nWB1,In Transit,2026-07-05 09:00:00\n";
        $upload = $this->makeUpload($user, $csv);
        $upload->update(['status' => 'scanning']);

        Livewire::actingAs($user)->test(RtsProcessor::class)
            ->set('currentUploadId', $upload->id)
            ->call('cancelUpload');

        $this->assertSame('canceled', $upload->fresh()->status);
    }

    public function test_plain_post_cancel_stops_a_scanning_upload(): void
    {
        $user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);
        $csv = "Waybill Number,Status,Submission Time\nWB1,In Transit,2026-07-05 09:00:00\n";
        $upload = $this->makeUpload($user, $csv);
        $upload->update(['status' => 'scanning']);

        $this->actingAs($user)
            ->post(route('tools.rts.cancel'), ['upload' => $upload->id])
            ->assertRedirect(route('tools.rts'));

        $this->assertSame('canceled', $upload->fresh()->status);
    }

    public function test_plain_post_cancel_ignores_another_users_upload(): void
    {
        $a = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);
        $b = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);
        $csv = "Waybill Number,Status,Submission Time\nWB1,In Transit,2026-07-05 09:00:00\n";
        $upload = $this->makeUpload($b, $csv);
        $upload->update(['status' => 'scanning']);

        $this->actingAs($a)->post(route('tools.rts.cancel'), ['upload' => $upload->id]);

        $this->assertSame('scanning', $upload->fresh()->status);
    }

    public function test_failed_job_marks_the_upload_failed(): void
    {
        // Simulates the worker being killed mid-run (MaxAttemptsExceeded) — the upload
        // must not be left stuck showing "scanning".
        $user = User::factory()->create();
        $csv = "Waybill Number,Status,Submission Time\nWB1,In Transit,2026-07-05 09:00:00\n";
        $upload = $this->makeUpload($user, $csv);
        $upload->update(['status' => 'scanning']);

        (new ProcessRtsUpload($upload->id))->failed(new \RuntimeException('worker restarted'));

        $this->assertSame('failed', $upload->fresh()->status);
    }

    public function test_sender_filter_label_tracks_the_real_selection(): void
    {
        // Regression: the "N selected" label used a stale Alpine counter that drifted
        // when a value was removed via a chip. It is now derived from the server state.
        $user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);

        Livewire::actingAs($user)->test(RtsMonitor::class)
            ->set('selectedSenders', ['SHOP 1', 'SHOP 2'])
            ->assertSee('2 selected')
            ->call('removeFilter', 'sender', 1)
            ->assertSee('1 selected')
            ->assertDontSee('2 selected');
    }
}
