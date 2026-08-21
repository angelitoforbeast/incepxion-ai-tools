<?php

namespace Tests\Feature;

use App\Livewire\Admin\RtsRecords;
use App\Models\FromJnt;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RtsRecordsAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $alice;
    private User $bob;

    protected function setUp(): void
    {
        parent::setUp();
        Plan::create(['name' => 'Free', 'slug' => 'free', 'daily_quota' => 20, 'monthly_quota' => 300]);

        $this->admin = User::factory()->create(['status' => 'approved', 'role' => 'admin', 'email_verified_at' => now()]);
        $this->alice = User::factory()->create(['name' => 'Alice', 'status' => 'approved', 'email_verified_at' => now()]);
        $this->bob   = User::factory()->create(['name' => 'Bob', 'status' => 'approved', 'email_verified_at' => now()]);

        FromJnt::insert([
            ['user_id' => $this->alice->id, 'waybill_number' => 'JT100', 'status' => 'Delivered',  'item_name' => 'SOLAR LIGHT', 'cod' => '499', 'submission_time' => '2026-08-01 09:00:00', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->alice->id, 'waybill_number' => 'JT200', 'status' => 'Returned',   'item_name' => 'SEAT COVER',  'cod' => '299', 'submission_time' => '2026-08-05 09:00:00', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->bob->id,   'waybill_number' => 'XY300', 'status' => 'In Transit', 'item_name' => 'LUNCH BOX',   'cod' => '199', 'submission_time' => '2026-08-10 09:00:00', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_non_admin_cannot_access(): void
    {
        $this->actingAs($this->alice)->get(route('admin.rts-records'))->assertForbidden();
    }

    public function test_admin_sees_rows_from_every_user(): void
    {
        $this->actingAs($this->admin)->get(route('admin.rts-records'))
            ->assertOk()
            ->assertSee('JT100')
            ->assertSee('XY300');
    }

    public function test_filtering_by_user(): void
    {
        Livewire::actingAs($this->admin)->test(RtsRecords::class)
            ->set('userId', (string) $this->bob->id)
            ->assertSee('XY300')
            ->assertDontSee('JT100');
    }

    public function test_filtering_by_status(): void
    {
        Livewire::actingAs($this->admin)->test(RtsRecords::class)
            ->set('status', 'Returned')
            ->assertSee('JT200')
            ->assertDontSee('JT100');
    }

    public function test_search_matches_waybill_prefix_and_item_name(): void
    {
        Livewire::actingAs($this->admin)->test(RtsRecords::class)
            ->set('search', 'JT1')
            ->assertSee('JT100')
            ->assertDontSee('XY300');

        Livewire::actingAs($this->admin)->test(RtsRecords::class)
            ->set('search', 'LUNCH')
            ->assertSee('XY300')
            ->assertDontSee('JT100');
    }

    public function test_filtering_by_submission_date_range(): void
    {
        Livewire::actingAs($this->admin)->test(RtsRecords::class)
            ->set('from', '2026-08-04')
            ->set('to', '2026-08-06')
            ->assertSee('JT200')
            ->assertDontSee('JT100')
            ->assertDontSee('XY300');
    }

    public function test_sorting_toggles_direction_and_rejects_unknown_columns(): void
    {
        Livewire::actingAs($this->admin)->test(RtsRecords::class)
            ->call('sort', 'waybill_number')
            ->assertSet('sortBy', 'waybill_number')
            ->assertSet('sortDir', 'asc')
            ->call('sort', 'waybill_number')
            ->assertSet('sortDir', 'desc')
            // A column that isn't whitelisted must not reach the query builder.
            ->call('sort', 'remarks; DROP TABLE users')
            ->assertSet('sortBy', 'waybill_number');
    }

    public function test_clear_filters_resets_everything(): void
    {
        Livewire::actingAs($this->admin)->test(RtsRecords::class)
            ->set('userId', (string) $this->bob->id)
            ->set('search', 'XY')
            ->call('clearFilters')
            ->assertSet('userId', '')
            ->assertSet('search', '')
            ->assertSee('JT100')
            ->assertSee('XY300');
    }
}
