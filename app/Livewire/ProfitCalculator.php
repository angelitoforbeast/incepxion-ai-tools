<?php

namespace App\Livewire;

use App\Models\ProfitCalculation;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Profit Calculator')]
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
        'cpp' => 220, 'cogs' => 150, 'sf' => 50, 'orders' => 68,
        'codPrice' => 795, 'codFee' => 0.02, 'rts' => 0.4, 'target' => 100,
    ];

    /**
     * Always the plain defaults.
     *
     * Inputs used to be restored from the user row, which meant the same numbers followed
     * the account onto any device — the one thing that told a user their typing was being
     * kept server-side. The page now starts fresh and the browser restores its own copy,
     * so the convenience stays and that signal doesn't.
     *
     * Nothing is lost: every calculation is still recorded in profit_calculations, which
     * is what the Profit Log reads.
     */
    public function mount(): void
    {
        $this->c1 = self::DEFAULTS;
        $this->c2 = self::DEFAULTS;
    }

    private function num($v): float
    {
        return is_numeric($v) ? (float) $v : 0.0;
    }

    /**
     * How wide each history column is, so a value that will not fit can be spotted before
     * the insert rather than after it. Keyed by column name.
     */
    private const COLUMN_MAX = [
        'cpp' => 9999999999.99,               // decimal(12,2)
        'cogs' => 9999999999.99,
        'shipping_fee' => 9999999999.99,
        'cod_price' => 9999999999.99,
        'orders' => 4294967295,               // unsignedInteger
        'cod_fee' => 9999.9999,               // decimal(8,4)
        'rts' => 99.9999,                     // decimal(6,4)
        'net_profit' => 999999999999.99,      // decimal(14,2)
        'target_net_profit' => 999999999999.99,
        'suggested_rts' => 999999.9999,       // decimal(10,4)
        'suggested_cpp' => 999999999999.99,
    ];

    /**
     * The only rules are the ones about what these fields mean.
     *
     * rts and cod_fee are rates, so 0–1 is their whole domain — the label already promises
     * 0.4 = 40%, and without this a typo of 40 returns a confidently wrong answer. Orders
     * are counted, so a fraction of one is not a thing. Costs are not negative.
     *
     * Money has no ceiling. Whatever figures a seller works with are their own business,
     * and there is no honest number to pick as "too much" — so nothing here guesses at one.
     * How wide the columns are is our problem to handle, not theirs to work around.
     */
    private function rules(int $which): array
    {
        $c = 'c'.$which;
        $money = ['required', 'numeric', 'min:0'];

        return [
            $c.'.cpp' => $money,
            $c.'.cogs' => $money,
            $c.'.sf' => $money,
            $c.'.codPrice' => $money,
            $c.'.target' => $money,
            $c.'.orders' => ['required', 'integer', 'min:1'],
            $c.'.codFee' => ['required', 'numeric', 'between:0,1'],
            $c.'.rts' => ['required', 'numeric', 'between:0,1'],
        ];
    }

    /**
     * Write the history row, or quietly don't.
     *
     * The row belongs to the admin's Profit Log; the answer on screen belongs to the user.
     * Neither a number too wide for its column nor a database having a bad minute is a
     * reason to take that answer away, so this never throws and never reports. Ad Copy
     * already works this way — the calculator was the one place that didn't, and a failed
     * insert took the whole calculation down with it.
     */
    private function record(array $row): void
    {
        try {
            foreach (self::COLUMN_MAX as $column => $max) {
                if (isset($row[$column]) && abs((float) $row[$column]) > $max) {
                    return;
                }
            }

            ProfitCalculation::create($row);
        } catch (\Throwable $e) {
            Log::warning('Profit calculation not recorded', [
                'user' => auth()->id(),
                'reason' => $e->getMessage(),
            ]);
        }
    }

    /** The two rate fields are the ones people get wrong, so they say the format outright. */
    private function messages(int $which): array
    {
        $c = 'c'.$which;

        return [
            $c.'.rts.between' => 'RTS must be between 0 and 1 (0.4 = 40%).',
            $c.'.codFee.between' => 'COD fee must be between 0 and 1 (0.02 = 2%).',
            $c.'.orders.integer' => 'Orders must be a whole number.',
            $c.'.orders.min' => 'Enter at least one order.',
        ];
    }

    /** Without these, messages read "The c1.cod price field...". */
    protected function validationAttributes(): array
    {
        $labels = [
            'cpp' => 'CPP', 'cogs' => 'COGS', 'sf' => 'shipping fee', 'orders' => 'orders',
            'codPrice' => 'COD price', 'codFee' => 'COD fee', 'rts' => 'RTS',
            'target' => 'target net profit',
        ];

        $out = [];
        foreach ([1, 2] as $n) {
            foreach ($labels as $key => $label) {
                $out['c'.$n.'.'.$key] = $label;
            }
        }

        return $out;
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
        $this->validate($this->rules($which), $this->messages($which));

        $c = $which === 2 ? $this->c2 : $this->c1;
        $net = $this->netProfit($c);

        if ($which === 2) {
            $this->net2 = $net;
        } else {
            $this->net1 = $net;
        }

        // History — visible to admins only (see Admin → Profit Log).
        $this->record([
            'user_id'           => auth()->id(),
            'type'              => 'net',
            'cpp'               => $this->num($c['cpp'] ?? 0),
            'cogs'              => $this->num($c['cogs'] ?? 0),
            'shipping_fee'      => $this->num($c['sf'] ?? 0),
            'orders'            => (int) $this->num($c['orders'] ?? 0),
            'cod_price'         => $this->num($c['codPrice'] ?? 0),
            'cod_fee'           => $this->num($c['codFee'] ?? 0),
            'rts'               => $this->num($c['rts'] ?? 0),
            'net_profit'        => round($net, 2),
            'target_net_profit' => $this->num($c['target'] ?? 0),
        ]);

        // The browser keeps its own copy; nothing to persist server-side here.
    }

    public function calcAdj(int $which): void
    {
        $this->validate($this->rules($which), $this->messages($which));

        $c = $which === 2 ? $this->c2 : $this->c1;

        $cpp = $this->num($c['cpp'] ?? 0);
        $cogs = $this->num($c['cogs'] ?? 0);
        $sf = $this->num($c['sf'] ?? 0);
        $orders = $this->num($c['orders'] ?? 0);
        $codPrice = $this->num($c['codPrice'] ?? 0);
        $codFee = $this->num($c['codFee'] ?? 0);
        $rts = $this->num($c['rts'] ?? 0);
        $target = $this->num($c['target'] ?? 0);

        // Zero orders used to be checked here; the rules reject it before we get this far.
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

            // History — record the adjustment run too (admin-only). A margin close to zero
            // sends the suggested RTS off to huge values, so this is one of the rows that
            // can be too wide to store; the suggestion is still shown either way.
            $this->record([
                'user_id'           => auth()->id(),
                'type'              => 'adjustment',
                'cpp'               => $cpp,
                'cogs'              => $cogs,
                'shipping_fee'      => $sf,
                'orders'            => (int) $orders,
                'cod_price'         => $codPrice,
                'cod_fee'           => $codFee,
                'rts'               => $rts,
                'net_profit'        => round($this->netProfit($c), 2),
                'target_net_profit' => $target,
                'suggested_rts'     => round($suggestedRts, 4),
                'suggested_cpp'     => round($suggestedCpp, 2),
            ]);
        }

        if ($which === 2) {
            $this->adj2 = $result;
        } else {
            $this->adj1 = $result;
        }

        // The browser keeps its own copy; nothing to persist server-side here.
    }

    public function render()
    {
        return view('livewire.profit-calculator');
    }
}
