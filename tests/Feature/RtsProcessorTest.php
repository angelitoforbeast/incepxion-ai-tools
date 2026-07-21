<?php

namespace Tests\Feature;

use App\Jobs\ProcessRtsUpload;
use App\Livewire\RtsMonitor;
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

    public function test_regression_pauses_for_confirmation_then_skips_on_continue(): void
    {
        $user = User::factory()->create();

        // Existing shipments: WB1 already delivered (final), WB3 still in transit.
        FromJnt::insert([
            ['user_id' => $user->id, 'waybill_number' => 'WB1', 'status' => 'Delivered',  'submission_time' => '2026-07-01 08:00:00', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $user->id, 'waybill_number' => 'WB3', 'status' => 'In Transit', 'submission_time' => '2026-07-02 08:00:00', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // File tries to move WB1 backward (delivered → in transit) = regression.
        $csv = "Waybill Number,Status,Item Name,Sender,Submission Time\n"
            ."WB1,In Transit,1 x LIP,ShopA,2026-07-01 08:00:00\n"
            ."WB2,In Transit,1 x LIP,ShopA,2026-07-05 09:00:00\n"
            ."WB3,Delivered,1 x LIP,ShopA,2026-07-02 08:00:00\n";

        $upload = $this->makeUpload($user, $csv);

        // First pass: should detect the regression and pause.
        ProcessRtsUpload::dispatchSync($upload->id);
        $upload->refresh();

        $this->assertSame('needs_confirmation', $upload->status);
        $this->assertSame(1, $upload->conflict_count);

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

        $csv = "Waybill Number,Status,Item Name,Sender,Submission Time\n"
            ."WBA,In Transit,1 x LIP,ShopA,2026-07-05 09:00:00\n"
            ."WBB,Delivered,1 x LIP,ShopA,2026-07-05 09:00:00\n";

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
        $csv = "Name,Age\nJuan,30\n"; // no J&T columns

        $upload = $this->makeUpload($user, $csv);

        try {
            ProcessRtsUpload::dispatchSync($upload->id);
        } catch (\Throwable $e) {
            // job rethrows after marking failed
        }

        $upload->refresh();
        $this->assertSame('failed', $upload->status);
        $this->assertStringContainsString('Wrong File', (string) $upload->error_message);
        // Source file is auto-deleted even on failure.
        Storage::disk('local')->assertMissing($upload->path);
    }

    public function test_source_file_deleted_after_success(): void
    {
        $user = User::factory()->create();
        $csv = "Waybill Number,Status,Submission Time\nWBZ,In Transit,2026-07-05 09:00:00\n";
        $upload = $this->makeUpload($user, $csv);

        ProcessRtsUpload::dispatchSync($upload->id);
        $upload->refresh();

        $this->assertSame('done', $upload->status);
        Storage::disk('local')->assertMissing($upload->path);
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

        $csv = "Waybill Number,Status,Submission Time\nWBX,In Transit,2026-07-05 09:00:00\n";
        $upload = $this->makeUpload($userA, $csv);
        ProcessRtsUpload::dispatchSync($upload->id);
        $upload->refresh();

        // No conflict for user A (their WBX is new), and B's row is untouched.
        $this->assertSame('done', $upload->status);
        $this->assertSame(1, $upload->inserted);
        $this->assertSame('In Transit', FromJnt::where('user_id', $userA->id)->where('waybill_number', 'WBX')->value('status'));
        $this->assertSame('Delivered', FromJnt::where('user_id', $userB->id)->where('waybill_number', 'WBX')->value('status'));
    }
}
