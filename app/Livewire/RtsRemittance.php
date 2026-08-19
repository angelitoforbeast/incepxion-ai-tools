<?php

namespace App\Livewire;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class RtsRemittance extends Component
{
    public string $from = '';
    public string $to = '';

    public function mount(): void
    {
        // Default: this month, ending on the LAST date that actually has data
        // (so empty future days up to "today" aren't shown).
        $today = Carbon::now('Asia/Manila');

        $last = DB::table('from_jnts')
            ->where('user_id', auth()->id())
            ->selectRaw('MAX(DATE(signingtime)) AS s, MAX(DATE(submission_time)) AS p')
            ->first();
        $lastDate = collect([$last->s ?? null, $last->p ?? null])->filter()->max();

        $to   = $lastDate ? Carbon::parse($lastDate) : $today->copy();
        $from = $today->copy()->startOfMonth();

        // If the latest data is before this month, show that month instead (avoid empty range).
        if ($to->lt($from)) {
            $from = $to->copy()->startOfMonth();
        }

        $this->from = $from->toDateString();
        $this->to   = $to->toDateString();
    }

    private function emptyTotals(): array
    {
        return [
            'delivered' => 0, 'cod_sum' => 0.0, 'cod_fee' => 0.0, 'cod_fee_vat' => 0.0,
            'picked' => 0, 'ship_cost' => 0.0, 'remittance' => 0.0,
        ];
    }

    private function compute($rates): array
    {
        // rates is a date-asc collection of UserFeeRate. Find the one effective on a date.
        $rateFor = function (string $d) use ($rates) {
            $found = null;
            foreach ($rates as $r) {
                if ($r->effective_date->toDateString() <= $d) {
                    $found = $r;
                } else {
                    break;
                }
            }

            return $found;
        };

        $userId = auth()->id();
        $start  = $this->from;
        $end    = $this->to ?: $this->from;

        $codExpr = DB::getDriverName() === 'mysql'
            ? "CAST(REPLACE(COALESCE(NULLIF(cod, ''), '0'), ',', '') AS DECIMAL(18,2))"
            : "CAST(REPLACE(COALESCE(NULLIF(cod, ''), '0'), ',', '') AS REAL)";

        $delivered = DB::table('from_jnts')
            ->where('user_id', $userId)
            ->selectRaw("DATE(signingtime) AS d, COUNT(*) AS delivered_count, COALESCE(SUM($codExpr),0) AS cod_sum")
            ->whereRaw("LOWER(status) LIKE 'delivered%'")
            ->whereNotNull('signingtime')
            ->whereBetween(DB::raw('DATE(signingtime)'), [$start, $end])
            ->groupBy('d')->orderBy('d')->get();

        $picked = DB::table('from_jnts')
            ->where('user_id', $userId)
            ->selectRaw('DATE(submission_time) AS d, COUNT(*) AS picked_count, COALESCE(SUM(COALESCE(total_shipping_cost,0)),0) AS ship_cost')
            ->whereNotNull('submission_time')
            ->whereBetween(DB::raw('DATE(submission_time)'), [$start, $end])
            ->groupBy('d')->orderBy('d')->get();

        $byDate = [];
        foreach ($delivered as $r) {
            $byDate[$r->d] = ['date' => $r->d, 'delivered' => (int) $r->delivered_count, 'cod_sum' => (float) $r->cod_sum, 'picked' => 0, 'ship_cost' => 0.0];
        }
        foreach ($picked as $r) {
            $byDate[$r->d] = $byDate[$r->d] ?? ['date' => $r->d, 'delivered' => 0, 'cod_sum' => 0.0, 'picked' => 0, 'ship_cost' => 0.0];
            $byDate[$r->d]['picked']    = (int) $r->picked_count;
            $byDate[$r->d]['ship_cost'] = (float) $r->ship_cost;
        }

        ksort($byDate);
        $rows = [];
        $uncovered = [];
        $totals = $this->emptyTotals();

        foreach ($byDate as $d => $v) {
            $rate = $rateFor($d);
            if (! $rate) {
                // Option B: no rate in effect for this date — exclude, warn.
                $uncovered[] = $d;

                continue;
            }

            $codRate  = (float) $rate->cod_fee_rate;
            $vatRate  = (float) $rate->cod_fee_vat_rate;
            $codSum   = (float) $v['cod_sum'];
            $shipCost = (float) $v['ship_cost'];
            $codFee   = round($codSum * $codRate, 2);
            $vat      = round($codFee * $vatRate, 2);
            $remit    = round($codSum - $codFee - $vat - $shipCost, 2);

            $rows[] = [
                'date' => $d, 'delivered' => (int) $v['delivered'], 'cod_sum' => $codSum,
                'cod_fee' => $codFee, 'cod_fee_vat' => $vat, 'picked' => (int) $v['picked'],
                'ship_cost' => $shipCost, 'remittance' => $remit,
            ];

            $totals['delivered']   += (int) $v['delivered'];
            $totals['cod_sum']     += $codSum;
            $totals['cod_fee']     += $codFee;
            $totals['cod_fee_vat'] += $vat;
            $totals['picked']      += (int) $v['picked'];
            $totals['ship_cost']   += $shipCost;
            $totals['remittance']  += $remit;
        }

        return ['rows' => $rows, 'totals' => $totals, 'uncovered' => $uncovered];
    }

    public function render()
    {
        $rates = auth()->user()->feeRates()->orderBy('effective_date')->get();
        $ready = $rates->isNotEmpty();

        $data = $ready ? $this->compute($rates) : ['rows' => [], 'totals' => $this->emptyTotals(), 'uncovered' => []];

        // Summary shows the most recent (current) rate.
        $current = $rates->last();

        // Bounds for the date pickers = the range of dates that actually have data.
        $bounds = DB::table('from_jnts')
            ->where('user_id', auth()->id())
            ->selectRaw('MIN(DATE(submission_time)) AS min_s, MIN(DATE(signingtime)) AS min_g, MAX(DATE(submission_time)) AS max_s, MAX(DATE(signingtime)) AS max_g')
            ->first();
        $minData = collect([$bounds->min_s ?? null, $bounds->min_g ?? null])->filter()->min();
        $maxData = collect([$bounds->max_s ?? null, $bounds->max_g ?? null])->filter()->max();

        return view('livewire.rts-remittance', [
            'rows'        => $data['rows'],
            'totals'      => $data['totals'],
            'uncovered'   => $data['uncovered'],
            'ratesReady'  => $ready,
            'codPercent'  => $current ? (float) $current->cod_fee_rate * 100 : null,
            'vatPercent'  => $current ? (float) $current->cod_fee_vat_rate * 100 : null,
            'earliestRate' => $rates->first()?->effective_date,
            'minData'     => $minData,
            'maxData'     => $maxData,
        ]);
    }
}
