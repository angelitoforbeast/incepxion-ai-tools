<?php

namespace Tests\Feature;

use App\Livewire\RtsMonitor;
use App\Models\FromJnt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RtsProjectionTest extends TestCase
{
    use RefreshDatabase;

    /** Add $n shipments on $date with the given status. */
    private function shipments(User $user, string $date, string $status, int $n, string $prefix): void
    {
        $rows = [];
        for ($i = 0; $i < $n; $i++) {
            $rows[] = [
                'user_id'         => $user->id,
                'waybill_number'  => $prefix.$i,
                'status'          => $status,
                'submission_time' => $date.' 09:00:00',
                'created_at'      => now(),
                'updated_at'      => now(),
            ];
        }
        FromJnt::insert($rows);
    }

    public function test_default_end_is_the_last_day_still_under_one_percent_in_transit(): void
    {
        $user = User::factory()->create();

        // Aug 1-2 fully settled; Aug 3 is half in transit, which pushes the cumulative
        // share over 1% — so the default cutoff should stop at Aug 2.
        $this->shipments($user, '2026-08-01', 'Delivered', 100, 'a');
        $this->shipments($user, '2026-08-02', 'Returned', 100, 'b');
        $this->shipments($user, '2026-08-03', 'In Transit', 100, 'c');

        Livewire::actingAs($user)->test(RtsMonitor::class)
            ->set('from', '2026-08-01')
            ->set('to', '2026-08-05')
            ->assertSet('projFrom', '2026-08-01')
            ->assertSet('projTo', '2026-08-02');
    }

    public function test_a_tiny_unsettled_tail_is_tolerated(): void
    {
        $user = User::factory()->create();

        // 1 in transit out of 1000 = 0.1%, under the threshold, so Aug 2 still counts.
        $this->shipments($user, '2026-08-01', 'Delivered', 999, 'a');
        $this->shipments($user, '2026-08-02', 'In Transit', 1, 'b');

        Livewire::actingAs($user)->test(RtsMonitor::class)
            ->set('from', '2026-08-01')
            ->set('to', '2026-08-05')
            ->assertSet('projTo', '2026-08-02');
    }

    public function test_estimated_rts_ignores_shipments_still_moving(): void
    {
        $user = User::factory()->create();

        // 200 RTS + 300 delivered + 3 in transit → 200/500 = 40.0%, not 200/503.
        $this->shipments($user, '2026-08-01', 'Returned', 200, 'r');
        $this->shipments($user, '2026-08-01', 'Delivered', 300, 'd');
        $this->shipments($user, '2026-08-01', 'In Transit', 3, 't');

        Livewire::actingAs($user)->test(RtsMonitor::class)
            ->set('from', '2026-08-01')
            ->set('to', '2026-08-05')
            ->assertViewHas('projection', fn ($p) => $p['estimatedRts'] === 40.0);
    }

    public function test_projection_window_start_is_adjustable(): void
    {
        $user = User::factory()->create();
        $this->shipments($user, '2026-08-01', 'Delivered', 10, 'a');
        $this->shipments($user, '2026-08-04', 'Returned', 10, 'b');

        // Moving the start past Aug 1 drops those 10 delivered from the cohort.
        Livewire::actingAs($user)->test(RtsMonitor::class)
            ->set('from', '2026-08-01')
            ->set('to', '2026-08-05')
            ->set('projFrom', '2026-08-04')
            ->set('projTo', '2026-08-05')
            ->assertViewHas('projection', fn ($p) => $p['total'] === 10 && $p['estimatedRts'] === 100.0);
    }

    public function test_projection_dates_stay_inside_the_selected_range(): void
    {
        $user = User::factory()->create();
        $this->shipments($user, '2026-08-02', 'Delivered', 5, 'a');

        Livewire::actingAs($user)->test(RtsMonitor::class)
            ->set('from', '2026-08-01')
            ->set('to', '2026-08-05')
            ->set('projTo', '2026-08-30')      // past the end
            ->assertSet('projTo', '2026-08-05')
            ->set('projFrom', '2026-07-01')    // before the start
            ->assertSet('projFrom', '2026-08-01');
    }

    public function test_start_cannot_pass_the_end(): void
    {
        $user = User::factory()->create();
        $this->shipments($user, '2026-08-02', 'Delivered', 5, 'a');

        Livewire::actingAs($user)->test(RtsMonitor::class)
            ->set('from', '2026-08-01')
            ->set('to', '2026-08-10')
            ->set('projTo', '2026-08-03')
            ->set('projFrom', '2026-08-08')    // later than the end
            ->assertSet('projTo', '2026-08-08'); // end follows the start
    }

    public function test_end_slider_moves_the_projection_end(): void
    {
        $user = User::factory()->create();
        $this->shipments($user, '2026-08-01', 'Delivered', 5, 'a');

        Livewire::actingAs($user)->test(RtsMonitor::class)
            ->set('from', '2026-08-01')
            ->set('to', '2026-08-10')
            ->set('projEndDays', 4)
            ->assertSet('projTo', '2026-08-05');
    }

    public function test_start_slider_moves_the_projection_start(): void
    {
        $user = User::factory()->create();
        $this->shipments($user, '2026-08-01', 'Delivered', 5, 'a');

        Livewire::actingAs($user)->test(RtsMonitor::class)
            ->set('from', '2026-08-01')
            ->set('to', '2026-08-10')
            ->set('projEndDays', 8)
            ->set('projStartDays', 3)
            ->assertSet('projFrom', '2026-08-04')
            ->assertSet('projTo', '2026-08-09');   // end untouched
    }

    public function test_start_slider_pushes_the_end_when_it_passes_it(): void
    {
        $user = User::factory()->create();
        $this->shipments($user, '2026-08-01', 'Delivered', 5, 'a');

        Livewire::actingAs($user)->test(RtsMonitor::class)
            ->set('from', '2026-08-01')
            ->set('to', '2026-08-10')
            ->set('projEndDays', 2)          // Aug 3
            ->set('projStartDays', 6)        // Aug 7 — past the end
            ->assertSet('projFrom', '2026-08-07')
            ->assertSet('projTo', '2026-08-07')
            ->assertSet('projEndDays', 6);   // slider follows
    }

    public function test_sliders_span_the_whole_selected_range(): void
    {
        $user = User::factory()->create();
        $this->shipments($user, '2026-08-05', 'Delivered', 5, 'a');

        // Both sliders are measured from the range start, not from the projection start,
        // so their positions are directly comparable on one scale.
        Livewire::actingAs($user)->test(RtsMonitor::class)
            ->set('from', '2026-08-01')
            ->set('to', '2026-08-11')
            ->set('projFrom', '2026-08-03')
            ->set('projTo', '2026-08-09')
            ->assertSet('projStartDays', 2)
            ->assertSet('projEndDays', 8);
    }
}
