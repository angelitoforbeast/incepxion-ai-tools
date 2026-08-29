<?php

namespace Tests\Feature;

use App\Livewire\Admin\ProfitHistory;
use App\Livewire\ProfitCalculator;
use App\Models\ProfitCalculation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfitCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_computes_net_profit_and_logs_history(): void
    {
        $user = User::factory()->create(['status' => 'approved']);

        Livewire::actingAs($user)->test(ProfitCalculator::class)
            ->assertSet('c1.cpp', 220)          // seeded default
            ->call('calcNet', 1);

        // Net = 68 × [ (1−0.4)(795×0.98 − 150) − (220 + 50) ] = 7307.28
        $calc = ProfitCalculation::first();
        $this->assertNotNull($calc);
        $this->assertSame($user->id, $calc->user_id);
        $this->assertSame('net', $calc->type);
        $this->assertEqualsWithDelta(7307.28, (float) $calc->net_profit, 0.01);
        $this->assertEqualsWithDelta(100, (float) $calc->target_net_profit, 0.01); // default target logged
    }

    public function test_calculate_adjustments_logs_history(): void
    {
        $user = User::factory()->create(['status' => 'approved']);

        Livewire::actingAs($user)->test(ProfitCalculator::class)
            ->call('calcAdj', 1);

        $adj = ProfitCalculation::where('type', 'adjustment')->first();
        $this->assertNotNull($adj);
        $this->assertSame($user->id, $adj->user_id);
        $this->assertNotNull($adj->suggested_rts);
        $this->assertNotNull($adj->suggested_cpp);
        $this->assertEqualsWithDelta(100, (float) $adj->target_net_profit, 0.01);
    }

    public function test_history_type_filter(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        ProfitCalculation::create(['user_id' => $admin->id, 'type' => 'net', 'cpp' => 1, 'cogs' => 1, 'shipping_fee' => 1, 'orders' => 1, 'cod_price' => 1, 'cod_fee' => 0, 'rts' => 0, 'net_profit' => 3131.31]);
        ProfitCalculation::create(['user_id' => $admin->id, 'type' => 'adjustment', 'cpp' => 1, 'cogs' => 1, 'shipping_fee' => 1, 'orders' => 1, 'cod_price' => 1, 'cod_fee' => 0, 'rts' => 0, 'net_profit' => 4242.42, 'suggested_rts' => 0.5, 'suggested_cpp' => 10]);

        Livewire::actingAs($admin)->test(ProfitHistory::class)
            ->assertSee('3,131.31')
            ->assertSee('4,242.42')
            ->set('type', 'adjustment')
            ->assertSee('4,242.42')
            ->assertDontSee('3,131.31');
    }

    // Inputs used to be held against the user row and restored on the next visit. That is
    // deliberately gone — the browser keeps its own copy now, so the same numbers no longer
    // follow the account onto another device. ProfitCalculatorPrivacyTest covers what
    // replaced it.

    public function test_adjustments_reject_zero_orders(): void
    {
        // This used to come back as $adj1['error'], a message in the results box below the
        // button. It is a validation error on the field itself now, which puts it next to
        // the input the user has to change.
        $user = User::factory()->create(['status' => 'approved']);

        Livewire::actingAs($user)->test(ProfitCalculator::class)
            ->set('c1.orders', 0)
            ->call('calcAdj', 1)
            ->assertHasErrors(['c1.orders' => 'min'])
            ->assertSet('adj1', null);
    }

    public function test_adjustments_suggests_values(): void
    {
        $user = User::factory()->create(['status' => 'approved']);

        Livewire::actingAs($user)->test(ProfitCalculator::class)
            ->call('calcAdj', 1)
            ->assertSet('adj1', fn ($a) => isset($a['rts'], $a['cpp']) && ! isset($a['error']));
    }

    public function test_admin_history_lists_calculations(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        ProfitCalculation::create(['user_id' => $admin->id, 'cpp' => 220, 'cogs' => 150, 'shipping_fee' => 35.8, 'orders' => 68, 'cod_price' => 795, 'cod_fee' => 0.02, 'rts' => 0.4, 'net_profit' => 8272.88]);

        Livewire::actingAs($admin)->test(ProfitHistory::class)
            ->assertSee($admin->email)
            ->assertSee('8,272.88');
    }

    public function test_admin_history_filters_by_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        ProfitCalculation::create(['user_id' => $u1->id, 'cpp' => 1, 'cogs' => 1, 'shipping_fee' => 1, 'orders' => 1, 'cod_price' => 1, 'cod_fee' => 0, 'rts' => 0, 'net_profit' => 1111.11]);
        ProfitCalculation::create(['user_id' => $u2->id, 'cpp' => 1, 'cogs' => 1, 'shipping_fee' => 1, 'orders' => 1, 'cod_price' => 1, 'cod_fee' => 0, 'rts' => 0, 'net_profit' => 2222.22]);

        Livewire::actingAs($admin)->test(ProfitHistory::class)
            ->assertSee('1,111.11')
            ->assertSee('2,222.22')
            ->set('selectedUsers', [$u1->id])
            ->assertSee('1,111.11')
            ->assertDontSee('2,222.22');
    }

    public function test_history_range_filter_on_net_profit(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        ProfitCalculation::create(['user_id' => $admin->id, 'cpp' => 1, 'cogs' => 1, 'shipping_fee' => 1, 'orders' => 1, 'cod_price' => 1, 'cod_fee' => 0, 'rts' => 0, 'net_profit' => 250.00]);
        ProfitCalculation::create(['user_id' => $admin->id, 'cpp' => 1, 'cogs' => 1, 'shipping_fee' => 1, 'orders' => 1, 'cod_price' => 1, 'cod_fee' => 0, 'rts' => 0, 'net_profit' => 5000.00]);

        Livewire::actingAs($admin)->test(ProfitHistory::class)
            ->set('min.net_profit', 1000)
            ->assertSee('5,000.00')
            ->assertDontSee('250.00');
    }
}
