<?php

namespace Tests\Feature;

use App\Livewire\Admin\ProfitHistory;
use App\Models\Plan;
use App\Models\ProfitCalculation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfitHistoryTest extends TestCase
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
    }

    private function calc(User $user, array $attrs = [], ?string $when = null): ProfitCalculation
    {
        $row = ProfitCalculation::create(array_merge([
            'user_id' => $user->id, 'type' => 'net', 'orders' => 100, 'net_profit' => 5000,
        ], $attrs));

        // created_at isn't fillable, so it has to go on after the insert — otherwise every
        // row is stamped "now" and a date-range assertion passes for the wrong reason.
        if ($when) {
            $row->forceFill(['created_at' => $when])->save();
        }

        return $row;
    }

    public function test_filtering_by_date_range(): void
    {
        $this->calc($this->alice, ['net_profit' => 1111], '2026-08-01 09:00:00');
        $this->calc($this->alice, ['net_profit' => 2222], '2026-08-20 09:00:00');

        Livewire::actingAs($this->admin)->test(ProfitHistory::class)
            ->set('from', '2026-08-15')
            ->set('to', '2026-08-25')
            ->assertSee('2,222')
            ->assertDontSee('1,111');
    }

    public function test_date_range_counts_towards_the_clear_button(): void
    {
        $this->calc($this->alice);

        Livewire::actingAs($this->admin)->test(ProfitHistory::class)
            ->set('from', '2026-08-01')
            ->assertViewHas('activeFilters', 1)
            ->set('to', '2026-08-31')
            ->assertViewHas('activeFilters', 2)
            ->call('clearFilters')
            ->assertSet('from', '')
            ->assertSet('to', '')
            ->assertViewHas('activeFilters', 0);
    }

    public function test_existing_filters_still_work_alongside_the_dates(): void
    {
        $this->calc($this->alice, ['type' => 'net', 'net_profit' => 1111]);
        $this->calc($this->bob, ['type' => 'adjustment', 'net_profit' => 2222]);

        Livewire::actingAs($this->admin)->test(ProfitHistory::class)
            ->set('type', 'adjustment')
            ->assertSee('2,222')
            ->assertDontSee('1,111');

        Livewire::actingAs($this->admin)->test(ProfitHistory::class)
            ->set('selectedUsers', [$this->alice->id])
            ->assertSee('1,111')
            ->assertDontSee('2,222');
    }

    public function test_page_size_can_be_changed(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->calc($this->alice);
        }

        Livewire::actingAs($this->admin)->test(ProfitHistory::class)
            ->assertViewHas('rows', fn ($r) => $r->count() === 25)
            ->set('perPage', 50)
            ->assertViewHas('rows', fn ($r) => $r->count() === 30);
    }
}
