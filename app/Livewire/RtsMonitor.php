<?php

namespace App\Livewire;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class RtsMonitor extends Component
{
    public string $from = '';
    public string $to = '';

    /** Multi-select filters (empty = all). */
    public array $selectedItems = [];
    public array $selectedSenders = [];
    public array $selectedCods = [];

    public function mount(): void
    {
        $this->from = Carbon::now('Asia/Manila')->subMonthNoOverflow()->startOfMonth()->toDateString();
        $this->to   = Carbon::now('Asia/Manila')->toDateString();
    }

    public function clearFilters(): void
    {
        $this->reset('selectedItems', 'selectedSenders', 'selectedCods');
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function range(): array
    {
        return [
            Carbon::parse($this->from, 'Asia/Manila')->startOfDay(),
            Carbon::parse($this->to, 'Asia/Manila')->endOfDay(),
        ];
    }

    /** Base query scoped to this user + date range (before the multi-select filters). */
    private function baseQuery()
    {
        [$fromDt, $toDt] = $this->range();

        return DB::table('from_jnts')
            ->where('user_id', auth()->id())
            ->whereBetween('submission_time', [$fromDt, $toDt]);
    }

    /**
     * Cascading option list: distinct values for $column within the date range,
     * narrowed by the OTHER active filters (its own filter is skipped so the user
     * can still see/uncheck what they picked).
     */
    private function optionsFor(string $column, string $skipModel): array
    {
        if (! $this->from || ! $this->to) {
            return [];
        }

        $q = $this->baseQuery();
        if ($skipModel !== 'selectedItems' && $this->selectedItems) {
            $q->whereIn('item_name', $this->selectedItems);
        }
        if ($skipModel !== 'selectedSenders' && $this->selectedSenders) {
            $q->whereIn('sender', $this->selectedSenders);
        }
        if ($skipModel !== 'selectedCods' && $this->selectedCods) {
            $q->whereIn('cod', $this->selectedCods);
        }

        return $q->whereNotNull($column)
            ->where($column, '<>', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->all();
    }

    /**
     * Aggregated RTS rows for the selected filters (this user only).
     *   - DELIVERED  = status is exactly "Delivered"
     *   - RTS        = "For Return" / "Returned"
     *   - IN TRANSIT = everything else
     */
    private function results(): array
    {
        if (! $this->from || ! $this->to) {
            return [];
        }

        $rows = $this->baseQuery()
            ->when($this->selectedItems, fn ($q) => $q->whereIn('item_name', $this->selectedItems))
            ->when($this->selectedSenders, fn ($q) => $q->whereIn('sender', $this->selectedSenders))
            ->when($this->selectedCods, fn ($q) => $q->whereIn('cod', $this->selectedCods))
            ->selectRaw("
                COALESCE(sender,'')    as sender,
                COALESCE(item_name,'') as item_name,
                COALESCE(cod,'')       as cod,
                COUNT(*)               as quantity,
                MIN(submission_time)   as min_sub,
                MAX(submission_time)   as max_sub,
                SUM(CASE WHEN LOWER(TRIM(status)) = 'delivered' THEN 1 ELSE 0 END) as delivered_count,
                SUM(CASE WHEN LOWER(status) LIKE '%return%' OR LOWER(status) LIKE '%rts%' THEN 1 ELSE 0 END) as rts_count
            ")
            ->groupBy('sender', 'item_name', 'cod')
            ->get();

        $fmt = function ($v) {
            try {
                return Carbon::parse($v, 'Asia/Manila')->format('Y-m-d');
            } catch (\Throwable $e) {
                return (string) $v;
            }
        };

        return collect($rows)->map(function ($r) use ($fmt) {
            $total     = max(1, (int) $r->quantity);
            $rts       = (int) $r->rts_count;
            $delivered = (int) $r->delivered_count;
            $transit   = max(0, (int) $r->quantity - $rts - $delivered);

            $rtsPct       = round($rts / $total * 100, 2);
            $deliveredPct = round($delivered / $total * 100, 2);
            $transitPct   = round(max(0, 100 - $rtsPct - $deliveredPct), 2);

            $settled    = $rts + $delivered;
            $currentRts = $settled > 0 ? round($rts / $settled * 100, 2) : null;

            return [
                'date_range'        => $fmt($r->min_sub).' to '.$fmt($r->max_sub),
                'sender'            => trim((string) $r->sender),
                'item'              => trim((string) $r->item_name),
                'cod'               => trim((string) $r->cod),
                'quantity'          => (int) $r->quantity,
                'rts_count'         => $rts,
                'delivered_count'   => $delivered,
                'transit_count'     => $transit,
                'rts_percent'       => $rtsPct,
                'delivered_percent' => $deliveredPct,
                'transit_percent'   => $transitPct,
                'current_rts'       => $currentRts,
            ];
        })->sortByDesc('rts_percent')->values()->all();
    }

    public function render()
    {
        $results = $this->results();

        $totalQty       = array_sum(array_column($results, 'quantity'));
        $totalRts       = array_sum(array_column($results, 'rts_count'));
        $totalDelivered = array_sum(array_column($results, 'delivered_count'));
        $totalTransit   = array_sum(array_column($results, 'transit_count'));
        $base = max(1, $totalQty);

        return view('livewire.rts-monitor', [
            'results'        => $results,
            'itemOptions'    => $this->optionsFor('item_name', 'selectedItems'),
            'senderOptions'  => $this->optionsFor('sender', 'selectedSenders'),
            'codOptions'     => $this->optionsFor('cod', 'selectedCods'),
            'activeFilters'  => count($this->selectedItems) + count($this->selectedSenders) + count($this->selectedCods),
            'totalQty'       => $totalQty,
            'totalRts'       => $totalRts,
            'totalDelivered' => $totalDelivered,
            'totalTransit'   => $totalTransit,
            'pctRts'         => round($totalRts / $base * 100, 1),
            'pctDelivered'   => round($totalDelivered / $base * 100, 1),
            'pctTransit'     => round($totalTransit / $base * 100, 1),
        ]);
    }
}
