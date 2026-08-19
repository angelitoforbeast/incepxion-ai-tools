<?php

namespace Tests\Feature;

use App\Livewire\RtsRemittance;
use App\Models\FromJnt;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\TestCase;

class RtsRemittanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Plan::create(['name' => 'Free', 'slug' => 'free', 'daily_quota' => 20, 'monthly_quota' => 300]);
    }

    private function seedRow(User $user, array $o): void
    {
        FromJnt::insert([array_merge([
            'user_id'             => $user->id,
            'waybill_number'      => 'WB'.uniqid(),
            'status'              => 'Delivered',
            'signingtime'         => '2026-08-05 10:00:00',
            'submission_time'     => '2026-08-05 08:00:00',
            'cod'                 => '1000',
            'total_shipping_cost' => 50,
            'created_at'          => now(),
            'updated_at'          => now(),
        ], $o)]);
    }

    public function test_prompts_to_set_rates_when_unconfigured(): void
    {
        $user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now(), 'remit_fees' => null]);

        Livewire::actingAs($user)->test(RtsRemittance::class)
            ->assertViewHas('ratesReady', false)
            ->assertSee('Set your fee rates first');
    }

    public function test_fee_rates_form_stores_decimals(): void
    {
        $user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now(), 'remit_fees' => null]);

        $this->actingAs($user);
        Volt::test('profile.fee-rates-form')
            ->set('codFeePercent', 2)
            ->set('codVatPercent', 12)
            ->call('save')
            ->assertHasNoErrors();

        $fees = $user->fresh()->remitFees();
        $this->assertEqualsWithDelta(0.02, $fees['cod_fee_rate'], 0.0001);
        $this->assertEqualsWithDelta(0.12, $fees['cod_fee_vat_rate'], 0.0001);
    }

    public function test_computes_remittance_per_date(): void
    {
        $user = User::factory()->create([
            'status' => 'approved', 'email_verified_at' => now(),
            'remit_fees' => ['cod_fee_rate' => 0.02, 'cod_fee_vat_rate' => 0.12, 'shipping_fee_per_order' => null],
        ]);
        // 1 delivered parcel: COD 1000, shipping 50, on 2026-08-05.
        $this->seedRow($user, ['cod' => '1000', 'total_shipping_cost' => 50]);

        Livewire::actingAs($user)->test(RtsRemittance::class)
            ->set('from', '2026-08-05')
            ->set('to', '2026-08-05')
            ->assertViewHas('totals', function ($t) {
                return $t['delivered'] === 1
                    && $t['picked'] === 1
                    && abs($t['cod_sum'] - 1000) < 0.01
                    && abs($t['cod_fee'] - 20) < 0.01          // 1000 * 2%
                    && abs($t['cod_fee_vat'] - 2.4) < 0.01     // 20 * 12%
                    && abs($t['ship_cost'] - 50) < 0.01
                    && abs($t['remittance'] - 927.6) < 0.01;   // 1000 - 20 - 2.4 - 50
            });
    }

    public function test_remittance_is_scoped_per_user(): void
    {
        $me = User::factory()->create([
            'status' => 'approved', 'email_verified_at' => now(),
            'remit_fees' => ['cod_fee_rate' => 0.02, 'cod_fee_vat_rate' => 0.12, 'shipping_fee_per_order' => null],
        ]);
        $other = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);

        $this->seedRow($me, ['cod' => '1000']);
        $this->seedRow($other, ['cod' => '9999']); // must NOT count toward $me

        Livewire::actingAs($me)->test(RtsRemittance::class)
            ->set('from', '2026-08-05')->set('to', '2026-08-05')
            ->assertViewHas('totals', fn ($t) => abs($t['cod_sum'] - 1000) < 0.01);
    }

    public function test_only_delivered_status_counts_for_cod(): void
    {
        $user = User::factory()->create([
            'status' => 'approved', 'email_verified_at' => now(),
            'remit_fees' => ['cod_fee_rate' => 0.02, 'cod_fee_vat_rate' => 0.12, 'shipping_fee_per_order' => null],
        ]);
        $this->seedRow($user, ['status' => 'Delivered', 'cod' => '1000']);
        $this->seedRow($user, ['status' => 'In Transit', 'cod' => '500']); // not delivered → no COD

        Livewire::actingAs($user)->test(RtsRemittance::class)
            ->set('from', '2026-08-05')->set('to', '2026-08-05')
            ->assertViewHas('totals', fn ($t) => $t['delivered'] === 1 && abs($t['cod_sum'] - 1000) < 0.01);
    }
}
