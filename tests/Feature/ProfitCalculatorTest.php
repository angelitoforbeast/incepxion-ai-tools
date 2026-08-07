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

        // Net = 68 × [ (1−0.4)(795×0.98 − 150) − (220 + 35.8) ] = 8272.88
        $calc = ProfitCalculation::first();
        $this->assertNotNull($calc);
        $this->assertSame($user->id, $calc->user_id);
        $this->assertEqualsWithDelta(8272.88, (float) $calc->net_profit, 0.01);
    }

    public function test_adjustments_error_when_orders_zero(): void
    {
        $user = User::factory()->create(['status' => 'approved']);

        Livewire::actingAs($user)->test(ProfitCalculator::class)
            ->set('c1.orders', 0)
            ->call('calcAdj', 1)
            ->assertSet('adj1', fn ($a) => isset($a['error']));
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
}
