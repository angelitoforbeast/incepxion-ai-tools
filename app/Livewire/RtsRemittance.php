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
        $this->from = Carbon::now('Asia/Manila')->startOfMonth()->toDateString();
        $this->to   = Carbon::now('Asia/Manila')->toDateString();
    }

    /** [cod_fee_rate, cod_fee_vat_rate] decimals, or nulls if unset. */
    private function rates(): array
    {
        $f = auth()->user()->remitFees();

        return [$f['cod_fee_rate'], $f['cod_fee_vat_rate']];
    }

    private function ratesReady(): bool
    {
        [$cod, $vat] = $this->rates();

        return $cod !== null && $vat !== null;
    }

    private function emptyTotals(): array
    {
        return [
            'delivered' => 0, 'cod_sum' => 0.0, 'cod_fee' => 0.0, 'cod_fee_vat' => 0.0,
            'picked' => 0, 'ship_cost' => 0.0, 'remittance' => 0.0,
        ];
    }

    private function compute(): array
    {
        [$codRate, $vatRate] = $this->rates();
        $codRate = (float) $codRate;
        $vatRate = (float) $vatRate;

        $userId = auth()->id();
        $start  = $this->from;
        $end    = $this->to ?: $this->from;

        // Robust COD cast (strip commas; blanks/null → 0). cod is stored as text.
        $codExpr = DB::getDriverName() === 'mysql'
            ? "CAST(REPLACE(COALESCE(NULLIF(cod, ''), '0'), ',', '') AS DECIMAL(18,2))"
            : "CAST(REPLACE(COALESCE(NULLIF(cod, ''), '0'), ',', '') AS REAL)";

        // Delivered + COD by signingtime date.
        $delivered = DB::table('from_jnts')
            ->where('user_id', $userId)
            ->selectRaw("DATE(signingtime) AS d, COUNT(*) AS delivered_count, COALESCE(SUM($codExpr),0) AS cod_sum")
            ->whereRaw("LOWER(status) LIKE 'delivered%'")
            ->whereNotNull('signingtime')
            ->whereBetween(DB::raw('DATE(signingtime)'), [$start, $end])
            ->groupBy('d')->orderBy('d')->get();

        // Pickups + ACTUAL shipping by submission_time date.
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
        $totals = $this->emptyTotals();

        foreach ($byDate as $d => $v) {
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

        return ['rows' => $rows, 'totals' => $totals];
    }

    public function render()
    {
        $ready = $this->ratesReady();
        $data  = $ready ? $this->compute() : ['rows' => [], 'totals' => $this->emptyTotals()];
        [$cod, $vat] = $this->rates();

        return view('livewire.rts-remittance', [
            'rows'        => $data['rows'],
            'totals'      => $data['totals'],
            'ratesReady'  => $ready,
            'codPercent'  => $cod !== null ? round($cod * 100, 4) : null,
            'vatPercent'  => $vat !== null ? round($vat * 100, 4) : null,
        ]);
    }
}
