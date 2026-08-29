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
 * Input bounds for the calculator.
 *
 * These are worth testing here rather than trusting the columns: rts is decimal(6,4) and
 * orders is unsigned, so out-of-range values used to reach MySQL and come back as a bare
 * "Server Error" with nothing telling the user which field to change. Locally the database
 * is SQLite, which accepts those values silently — so the column can never be the guard,
 * and only a check in PHP fails the same way in both places.
 */
class ProfitCalculatorGuardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Plan::create(['name' => 'Free', 'slug' => 'free', 'daily_quota' => 20, 'monthly_quota' => 300]);
        $this->user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);
    }

    private function calc(array $overrides = [])
    {
        return Livewire::actingAs($this->user)->test(ProfitCalculator::class)
            ->set('c1', array_merge([
                'cpp' => 220, 'cogs' => 150, 'sf' => 50, 'orders' => 68,
                'codPrice' => 795, 'codFee' => 0.02, 'rts' => 0.4, 'target' => 100,
            ], $overrides));
    }

    public function test_a_percentage_typed_into_rts_is_rejected(): void
    {
        // 400 is what produced the 500: decimal(6,4) tops out at 99.9999.
        $this->calc(['rts' => 400])
            ->call('calcNet', 1)
            ->assertHasErrors(['c1.rts' => 'between']);

        $this->assertSame(0, ProfitCalculation::count());
    }

    public function test_an_rts_that_fits_the_column_but_means_nonsense_is_still_rejected(): void
    {
        // 40 fits decimal(6,4), so the database accepted it and returned a wildly wrong
        // answer with no complaint. That silent case matters more than the loud one.
        $this->calc(['rts' => 40])
            ->call('calcNet', 1)
            ->assertHasErrors(['c1.rts' => 'between']);

        $this->assertSame(0, ProfitCalculation::count());
    }

    public function test_a_valid_rts_still_calculates_and_records(): void
    {
        $this->calc(['cpp' => 100, 'cogs' => 100, 'sf' => 50, 'orders' => 10,
            'codPrice' => 1000, 'codFee' => 0, 'rts' => 0, 'target' => 0])
            ->call('calcNet', 1)
            ->assertHasNoErrors()
            ->assertSet('net1', 7500.0);

        $this->assertSame(1, ProfitCalculation::count());
    }

    public function test_negative_orders_are_rejected(): void
    {
        // orders is unsignedInteger — a negative reached MySQL as an out-of-range error.
        $this->calc(['orders' => -5])
            ->call('calcNet', 1)
            ->assertHasErrors(['c1.orders' => 'min']);
    }

    public function test_fractional_orders_are_rejected(): void
    {
        // 68.7 used to compute against 68.7 but record 68, so the log disagreed with the
        // number the user was shown.
        $this->calc(['orders' => 68.7])
            ->call('calcNet', 1)
            ->assertHasErrors(['c1.orders' => 'integer']);
    }

    public function test_zero_orders_are_rejected(): void
    {
        $this->calc(['orders' => 0])
            ->call('calcNet', 1)
            ->assertHasErrors(['c1.orders' => 'min']);
    }

    public function test_large_amounts_of_money_are_allowed(): void
    {
        // Money has no invented ceiling — a seller's figures are their own business, and
        // the only real limit is the width of the column.
        $this->calc(['cpp' => 5000000, 'cogs' => 3000000, 'codPrice' => 90000000, 'orders' => 2])
            ->call('calcNet', 1)
            ->assertHasNoErrors();

        $this->assertSame(1, ProfitCalculation::count());
    }

    public function test_a_result_too_wide_to_store_is_still_shown_to_the_user(): void
    {
        // Every field passes on its own; it is the multiplication that will not fit the
        // column. The history row is the admin's, so it is dropped without a word — the
        // answer is the user's, and they still get it.
        $this->calc(['codPrice' => 9000000000, 'cogs' => 0, 'cpp' => 0, 'sf' => 0,
            'codFee' => 0, 'rts' => 0, 'orders' => 1000000])
            ->call('calcNet', 1)
            ->assertHasNoErrors()
            ->assertSet('net1', 9.0E+15);

        $this->assertSame(0, ProfitCalculation::count());
    }

    public function test_nothing_on_screen_hints_that_a_row_was_dropped(): void
    {
        // The point of dropping it quietly is that the page looks exactly as it would on a
        // normal run. An apology, a warning, or a greyed-out result would all give it away.
        $this->calc(['codPrice' => 9000000000, 'cogs' => 0, 'cpp' => 0, 'sf' => 0,
            'codFee' => 0, 'rts' => 0, 'orders' => 1000000])
            ->call('calcNet', 1)
            ->assertDontSee('too large')
            ->assertDontSee('could not')
            ->assertDontSee('not saved');
    }

    public function test_a_cod_fee_above_one_is_rejected(): void
    {
        $this->calc(['codFee' => 2])
            ->call('calcNet', 1)
            ->assertHasErrors(['c1.codFee' => 'between']);
    }

    public function test_the_adjustment_button_is_guarded_too(): void
    {
        // Both buttons write the same columns; guarding only one leaves the other failing.
        $this->calc(['rts' => 400])
            ->call('calcAdj', 1)
            ->assertHasErrors(['c1.rts' => 'between']);

        $this->assertSame(0, ProfitCalculation::count());
    }

    public function test_the_second_calculator_is_guarded_independently(): void
    {
        Livewire::actingAs($this->user)->test(ProfitCalculator::class)
            ->set('c2.rts', 400)
            ->call('calcNet', 2)
            ->assertHasErrors(['c2.rts' => 'between'])
            ->assertHasNoErrors(['c1.rts']);
    }

    public function test_a_bad_value_in_one_calculator_does_not_block_the_other(): void
    {
        Livewire::actingAs($this->user)->test(ProfitCalculator::class)
            ->set('c2.rts', 400)
            ->call('calcNet', 1)
            ->assertHasNoErrors();
    }

    public function test_the_message_is_actually_rendered_on_the_page(): void
    {
        // The rules are only half of it: without @error in the blade the request still
        // fails validation, but the page comes back looking untouched and the button
        // appears to do nothing at all.
        $this->calc(['rts' => 400])
            ->call('calcNet', 1)
            ->assertSee('RTS must be between 0 and 1 (0.4 = 40%).');
    }

    public function test_the_rts_message_says_what_the_field_expects(): void
    {
        // "The c1.rts field must be between 0 and 1" would leave the user guessing at the
        // format; the label promises 0.4 = 40% and the error has to agree with it.
        $this->calc(['rts' => 400])
            ->call('calcNet', 1)
            ->assertHasErrors('c1.rts');

        $errors = Livewire::actingAs($this->user)->test(ProfitCalculator::class)
            ->set('c1.rts', 400)
            ->call('calcNet', 1)
            ->errors()
            ->get('c1.rts');

        $this->assertSame('RTS must be between 0 and 1 (0.4 = 40%).', $errors[0]);
    }
}
