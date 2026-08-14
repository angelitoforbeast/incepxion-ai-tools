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

    // Per-user rates — entered as PERCENT in the UI, stored as decimals.
    public $codFeePercent = null;   // e.g. 2 → 0.02
    public $codVatPercent = null;   // e.g. 12 → 0.12
    public $shipFee = null;         // peso, optional (anomaly check only)

    public bool $showFees = false;

    public function mount(): void
    {
        $this->from = Carbon::now('Asia/Manila')->startOfMonth()->toDateString();
        $this->to   = Carbon::now('Asia/Manila')->toDateString();

        $fees = auth()->user()->remitFees();
        $this->codFeePercent = $fees['cod_fee_rate'] !== null ? round($fees['cod_fee_rate'] * 100, 4) : null;
        $this->codVatPercent = $fees['cod_fee_vat_rate'] !== null ? round($fees['cod_fee_vat_rate'] * 100, 4) : null;
        $this->shipFee       = $fees['shipping_fee_per_order'];

        // Prompt to set rates if either required rate is missing.
        $this->showFees = ($this->codFeePercent === null || $this->codVatPercent === null);
    }

    public function saveFees(): void
    {
        $this->validate([
            'codFeePercent' => ['required', 'numeric', 'min:0', 'max:100'],
            'codVatPercent' => ['required', 'numeric', 'min:0', 'max:100'],
            'shipFee'       => ['nullable', 'numeric', 'min:0'],
        ], [], [
            'codFeePercent' => 'COD fee rate',
            'codVatPercent' => 'VAT rate',
            'shipFee'       => 'shipping fee',
        ]);

        auth()->user()->update(['remit_fees' => [
            'cod_fee_rate'           => round(((float) $this->codFeePercent) / 100, 6),
            'cod_fee_vat_rate'       => round(((float) $this->codVatPercent) / 100, 6),
            'shipping_fee_per_order' => ($this->shipFee === null || $this->shipFee === '') ? null : (float) $this->shipFee,
        ]]);

        $this->showFees = false;
        $this->dispatch('notify', message: 'Fee rates saved.', type: 'success');
    }

    public function toggleFees(): void
    {
        $this->showFees = ! $this->showFees;
    }

    /** True once both required rates are configured. */
    private function ratesReady(): bool
    {
        return is_numeric($this->codFeePercent) && is_numeric($this->codVatPercent);
    }

    private function emptyTotals(): array
    {
        return [
            'delivered' => 0, 'cod_sum' => 0.0, 'cod_fee' => 0.0, 'cod_fee_vat' => 0.0,
            'picked' => 0, 'ship_cost' => 0.0, 'remittance' => 0.0, 'anomaly' => 0,
        ];
    }

    private function compute(): array
    {
        $userId = auth()->id();
        $start  = $this->from;
        $end    = $this->to ?: $this->from;

        $codRate    = ((float) $this->codFeePercent) / 100;
        $vatRate    = ((float) $this->codVatPercent) / 100;
        $expectedSF = ($this->shipFee === null || $this->shipFee === '') ? null : (float) $this->shipFee;

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

        // SF anomaly per date (only if an expected shipping fee is configured).
        $anomaliesByDate = [];
        if ($expectedSF !== null) {
            $anom = DB::table('from_jnts')
                ->where('user_id', $userId)
                ->selectRaw('DATE(submission_time) AS d, total_shipping_cost AS sf, COUNT(*) AS cnt')
                ->whereNotNull('submission_time')
                ->whereBetween(DB::raw('DATE(submission_time)'), [$start, $end])
                ->groupBy('d', 'total_shipping_cost')->get();
            foreach ($anom as $row) {
                if (abs((float) $row->sf - $expectedSF) > 0.01) {
                    $anomaliesByDate[$row->d][] = ['sf' => (float) $row->sf, 'count' => (int) $row->cnt];
                }
            }
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

            $anoms      = $anomaliesByDate[$d] ?? [];
            $anomCount  = array_sum(array_column($anoms, 'count'));

            $rows[] = [
                'date' => $d, 'delivered' => (int) $v['delivered'], 'cod_sum' => $codSum,
                'cod_fee' => $codFee, 'cod_fee_vat' => $vat, 'picked' => (int) $v['picked'],
                'ship_cost' => $shipCost, 'remittance' => $remit,
                'anomalies' => $anoms, 'anomaly_count' => $anomCount,
            ];

            $totals['delivered']   += (int) $v['delivered'];
            $totals['cod_sum']     += $codSum;
            $totals['cod_fee']     += $codFee;
            $totals['cod_fee_vat'] += $vat;
            $totals['picked']      += (int) $v['picked'];
            $totals['ship_cost']   += $shipCost;
            $totals['remittance']  += $remit;
            $totals['anomaly']     += $anomCount;
        }

        return ['rows' => $rows, 'totals' => $totals];
    }

    public function render()
    {
        $ready = $this->ratesReady();
        $data  = $ready ? $this->compute() : ['rows' => [], 'totals' => $this->emptyTotals()];

        return view('livewire.rts-remittance', [
            'rows'         => $data['rows'],
            'totals'       => $data['totals'],
            'ratesReady'   => $ready,
            'expectedSF'   => ($this->shipFee === null || $this->shipFee === '') ? null : (float) $this->shipFee,
        ]);
    }
}
