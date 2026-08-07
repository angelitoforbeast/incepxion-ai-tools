<?php

namespace App\Livewire;

use App\Models\ProfitCalculation;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ProfitCalculator extends Component
{
    /** Two independent calculators (dual view for comparing scenarios). */
    public array $c1 = [];
    public array $c2 = [];

    // Results
    public ?float $net1 = null;
    public ?float $net2 = null;
    public ?array $adj1 = null;
    public ?array $adj2 = null;

    private const DEFAULTS = [
        'cpp' => 220, 'cogs' => 150, 'sf' => 35.8, 'orders' => 68,
        'codPrice' => 795, 'codFee' => 0.02, 'rts' => 0.4, 'target' => 100,
    ];

    public function mount(): void
    {
        $this->c1 = self::DEFAULTS;
        $this->c2 = self::DEFAULTS;
    }

    private function num($v): float
    {
        return is_numeric($v) ? (float) $v : 0.0;
    }

    /** Net Profit = orders × [ (1−rts) × (codPrice×(1−codFee) − cogs) − (cpp + sf) ] */
    private function netProfit(array $c): float
    {
        $cpp = $this->num($c['cpp'] ?? 0);
        $cogs = $this->num($c['cogs'] ?? 0);
        $sf = $this->num($c['sf'] ?? 0);
        $orders = $this->num($c['orders'] ?? 0);
        $codPrice = $this->num($c['codPrice'] ?? 0);
        $codFee = $this->num($c['codFee'] ?? 0);
        $rts = $this->num($c['rts'] ?? 0);

        return $orders * ((1 - $rts) * ($codPrice * (1 - $codFee) - $cogs) - ($cpp + $sf));
    }

    public function calcNet(int $which): void
    {
        $c = $which === 2 ? $this->c2 : $this->c1;
        $net = $this->netProfit($c);

        if ($which === 2) {
            $this->net2 = $net;
        } else {
            $this->net1 = $net;
        }

        // History — visible to admins only (see Admin → Profit Log).
        ProfitCalculation::create([
            'user_id'      => auth()->id(),
            'cpp'          => $this->num($c['cpp'] ?? 0),
            'cogs'         => $this->num($c['cogs'] ?? 0),
            'shipping_fee' => $this->num($c['sf'] ?? 0),
            'orders'       => (int) $this->num($c['orders'] ?? 0),
            'cod_price'    => $this->num($c['codPrice'] ?? 0),
            'cod_fee'      => $this->num($c['codFee'] ?? 0),
            'rts'          => $this->num($c['rts'] ?? 0),
            'net_profit'   => round($net, 2),
        ]);
    }

    public function calcAdj(int $which): void
    {
        $c = $which === 2 ? $this->c2 : $this->c1;

        $cpp = $this->num($c['cpp'] ?? 0);
        $cogs = $this->num($c['cogs'] ?? 0);
        $sf = $this->num($c['sf'] ?? 0);
        $orders = $this->num($c['orders'] ?? 0);
        $codPrice = $this->num($c['codPrice'] ?? 0);
        $codFee = $this->num($c['codFee'] ?? 0);
        $rts = $this->num($c['rts'] ?? 0);
        $target = $this->num($c['target'] ?? 0);

        if ($orders == 0) {
            $result = ['error' => 'Number of orders cannot be zero.'];
        } else {
            $margin = $codPrice * (1 - $codFee) - $cogs; // per-unit contribution before CPP/SF
            if ($margin == 0.0) {
                $result = ['error' => 'COD price minus fees and COGS is zero — cannot suggest RTS.'];
            } else {
                $suggestedRts = 1 - ((($cpp + $sf) + ($target / $orders)) / $margin);
                $suggestedCpp = (1 - $rts) * $margin - $sf - ($target / $orders);
                $result = [
                    'rts' => $suggestedRts,
                    'rts_ok' => ($suggestedRts >= 0 && $suggestedRts <= 1),
                    'cpp' => $suggestedCpp,
                ];
            }
        }

        if ($which === 2) {
            $this->adj2 = $result;
        } else {
            $this->adj1 = $result;
        }
    }

    public function render()
    {
        return view('livewire.profit-calculator');
    }
}
