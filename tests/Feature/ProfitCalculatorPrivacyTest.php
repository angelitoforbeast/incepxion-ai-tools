<?php

namespace Tests\Feature;

use App\Livewire\ProfitCalculator;
use App\Models\Plan;
use App\Models\ProfitCalculation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The calculator must not carry a user's numbers between devices.
 *
 * Values held against the account reappear wherever that account signs in, which is the
 * one thing that shows a user their typing is being kept server-side. The form now starts
 * from the defaults and the browser restores its own copy.
 */
class ProfitCalculatorPrivacyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Plan::create(['name' => 'Free', 'slug' => 'free', 'daily_quota' => 20, 'monthly_quota' => 300]);
        $this->user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);
    }

    public function test_opening_the_page_shows_the_defaults(): void
    {
        Livewire::actingAs($this->user)->test(ProfitCalculator::class)
            ->assertSet('c1.cpp', 220)
            ->assertSet('c1.orders', 68)
            ->assertSet('c2.codPrice', 795);
    }

    public function test_stored_values_on_the_account_are_not_restored(): void
    {
        // An older row, or one written by hand, must not surface on a fresh device.
        $this->user->forceFill(['profit_inputs' => [
            'c1' => ['cpp' => 999, 'orders' => 12345],
            'c2' => ['cpp' => 888],
        ]])->save();

        Livewire::actingAs($this->user)->test(ProfitCalculator::class)
            ->assertSet('c1.cpp', 220)      // the default, not 999
            ->assertSet('c1.orders', 68)    // the default, not 12345
            ->assertSet('c2.cpp', 220);
    }

    public function test_typing_writes_nothing_to_the_account(): void
    {
        Livewire::actingAs($this->user)->test(ProfitCalculator::class)
            ->set('c1.cpp', 555)
            ->set('c2.orders', 42);

        $this->assertNull($this->user->fresh()->profit_inputs);
    }

    public function test_calculating_writes_nothing_to_the_account(): void
    {
        Livewire::actingAs($this->user)->test(ProfitCalculator::class)
            ->set('c1.cpp', 555)
            ->call('calcNet', 1)
            ->call('calcAdj', 1);

        $this->assertNull($this->user->fresh()->profit_inputs);
    }

    public function test_calculations_are_still_recorded_for_the_admin_log(): void
    {
        // Removing the per-account restore must not cost the history the Profit Log reads.
        Livewire::actingAs($this->user)->test(ProfitCalculator::class)
            ->set('c1.cpp', 555)
            ->call('calcNet', 1);

        $row = ProfitCalculation::where('user_id', $this->user->id)->latest('id')->first();

        $this->assertNotNull($row);
        $this->assertSame('net', $row->type);
        $this->assertEqualsWithDelta(555, (float) $row->cpp, 0.01);
    }

    public function test_the_maths_still_works_on_restored_values(): void
    {
        // The browser pushes its saved numbers to the server; Calculate has to use those,
        // not the defaults it mounted with.
        Livewire::actingAs($this->user)->test(ProfitCalculator::class)
            ->set('c1', ['cpp' => 100, 'cogs' => 100, 'sf' => 50, 'orders' => 10,
                         'codPrice' => 1000, 'codFee' => 0, 'rts' => 0, 'target' => 0])
            ->call('calcNet', 1)
            // 10 × [(1−0) × (1000 − 100) − (100 + 50)] = 7500
            ->assertSet('net1', 7500.0);
    }
}
