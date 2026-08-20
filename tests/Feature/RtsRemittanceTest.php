<?php

namespace Tests\Feature;

use App\Livewire\RtsRemittance;
use App\Models\FromJnt;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserFeeRate;
use Illuminate\Support\Carbon;
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

    private function setRate(User $user, string $effective, float $codPercent, float $vatPercent): void
    {
        UserFeeRate::create([
            'user_id'          => $user->id,
            'effective_date'   => $effective,
            'cod_fee_rate'     => $codPercent / 100,
            'cod_fee_vat_rate' => $vatPercent / 100,
        ]);
    }

    private function seedRow(User $user, string $date, array $o = []): void
    {
        FromJnt::insert([array_merge([
            'user_id'             => $user->id,
            'waybill_number'      => 'WB'.uniqid(),
            'status'              => 'Delivered',
            'signingtime'         => "{$date} 10:00:00",
            'submission_time'     => "{$date} 08:00:00",
            'cod'                 => '1000',
            'total_shipping_cost' => 50,
            'created_at'          => now(),
            'updated_at'          => now(),
        ], $o)]);
    }

    public function test_prompts_to_set_rates_when_none_exist(): void
    {
        $user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);

        Livewire::actingAs($user)->test(RtsRemittance::class)
            ->assertViewHas('ratesReady', false)
            ->assertSee('Set your fee rates first');
    }

    public function test_fee_rates_form_adds_a_dated_rate(): void
    {
        $user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);

        $this->actingAs($user);
        Volt::test('profile.fee-rates-form')
            ->set('newDate', '2026-01-01')
            ->set('newCod', 2)
            ->set('newVat', 12)
            ->call('addRate')
            ->assertHasNoErrors();

        $rate = UserFeeRate::where('user_id', $user->id)->first();
        $this->assertNotNull($rate);
        $this->assertEqualsWithDelta(0.02, (float) $rate->cod_fee_rate, 0.0001);
        $this->assertEqualsWithDelta(0.12, (float) $rate->cod_fee_vat_rate, 0.0001);
    }

    public function test_add_form_is_hidden_until_requested(): void
    {
        $user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);
        $this->actingAs($user);

        Volt::test('profile.fee-rates-form')
            ->assertSet('adding', false)
            ->call('startAdd')
            ->assertSet('adding', true)
            ->call('cancelAdd')
            ->assertSet('adding', false);
    }

    public function test_add_form_stays_open_on_validation_error_and_closes_on_save(): void
    {
        $user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);
        $this->actingAs($user);

        Volt::test('profile.fee-rates-form')
            ->call('startAdd')
            ->call('addRate')                 // nothing filled in
            ->assertHasErrors(['newDate'])
            ->assertSet('adding', true)       // must not close, or the errors vanish
            ->set('newDate', '2026-01-01')
            ->set('newCod', 2)
            ->set('newVat', 12)
            ->call('addRate')
            ->assertHasNoErrors()
            ->assertSet('adding', false);
    }

    public function test_computes_remittance_with_effective_rate(): void
    {
        $user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);
        $this->setRate($user, '2026-01-01', 2, 12);
        $this->seedRow($user, '2026-08-05', ['cod' => '1000', 'total_shipping_cost' => 50]);

        Livewire::actingAs($user)->test(RtsRemittance::class)
            ->set('from', '2026-08-05')->set('to', '2026-08-05')
            ->assertViewHas('totals', function ($t) {
                return $t['delivered'] === 1 && $t['picked'] === 1
                    && abs($t['cod_sum'] - 1000) < 0.01
                    && abs($t['cod_fee'] - 20) < 0.01       // 1000 * 2%
                    && abs($t['cod_fee_vat'] - 2.4) < 0.01  // 20 * 12%
                    && abs($t['ship_cost'] - 50) < 0.01
                    && abs($t['remittance'] - 927.6) < 0.01;
            });
    }

    public function test_rate_change_mid_range_applies_per_date(): void
    {
        $user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);
        $this->setRate($user, '2026-01-01', 2, 12);  // 2% from Jan 1
        $this->setRate($user, '2026-08-10', 1, 12);  // 1% from Aug 10

        $this->seedRow($user, '2026-08-05', ['cod' => '1000']); // uses 2% → fee 20
        $this->seedRow($user, '2026-08-15', ['cod' => '1000']); // uses 1% → fee 10

        Livewire::actingAs($user)->test(RtsRemittance::class)
            ->set('from', '2026-08-01')->set('to', '2026-08-31')
            ->assertViewHas('rows', function ($rows) {
                $byDate = collect($rows)->keyBy('date');

                return abs($byDate['2026-08-05']['cod_fee'] - 20) < 0.01
                    && abs($byDate['2026-08-15']['cod_fee'] - 10) < 0.01;
            });
    }

    public function test_dates_before_earliest_rate_are_excluded_and_warned(): void
    {
        $user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);
        $this->setRate($user, '2026-08-10', 2, 12); // earliest rate starts Aug 10

        $this->seedRow($user, '2026-08-05', ['cod' => '1000']); // BEFORE earliest → excluded
        $this->seedRow($user, '2026-08-15', ['cod' => '1000']); // covered

        Livewire::actingAs($user)->test(RtsRemittance::class)
            ->set('from', '2026-08-01')->set('to', '2026-08-31')
            ->assertViewHas('uncovered', fn ($u) => in_array('2026-08-05', $u))
            ->assertViewHas('totals', fn ($t) => $t['delivered'] === 1 && abs($t['cod_sum'] - 1000) < 0.01);
    }

    public function test_default_range_is_this_month_up_to_last_data_date(): void
    {
        Carbon::setTestNow('2026-08-19 10:00:00');

        $user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);
        $this->setRate($user, '2026-01-01', 2, 12);
        $this->seedRow($user, '2026-08-15'); // last data date

        Livewire::actingAs($user)->test(RtsRemittance::class)
            ->assertSet('from', '2026-08-01')
            ->assertSet('to', '2026-08-15');

        Carbon::setTestNow();
    }

    public function test_remittance_is_scoped_per_user(): void
    {
        $me = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);
        $other = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);
        $this->setRate($me, '2026-01-01', 2, 12);

        $this->seedRow($me, '2026-08-05', ['cod' => '1000']);
        $this->seedRow($other, '2026-08-05', ['cod' => '9999']); // must NOT count

        Livewire::actingAs($me)->test(RtsRemittance::class)
            ->set('from', '2026-08-05')->set('to', '2026-08-05')
            ->assertViewHas('totals', fn ($t) => abs($t['cod_sum'] - 1000) < 0.01);
    }

    public function test_only_delivered_status_counts_for_cod(): void
    {
        $user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);
        $this->setRate($user, '2026-01-01', 2, 12);
        $this->seedRow($user, '2026-08-05', ['status' => 'Delivered', 'cod' => '1000']);
        $this->seedRow($user, '2026-08-05', ['status' => 'In Transit', 'cod' => '500']);

        Livewire::actingAs($user)->test(RtsRemittance::class)
            ->set('from', '2026-08-05')->set('to', '2026-08-05')
            ->assertViewHas('totals', fn ($t) => $t['delivered'] === 1 && abs($t['cod_sum'] - 1000) < 0.01);
    }
}
