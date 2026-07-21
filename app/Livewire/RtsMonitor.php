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

    /** Projection cutoff: number of days from $from (partial end date = from + partialDays). */
    public ?int $partialDays = null;

    /** Default projection window aims for at least this many shipments (or all, if fewer). */
    private const PROJECTION_MIN = 300;

    public function mount(): void
    {
        $this->from = Carbon::now('Asia/Manila')->subMonthNoOverflow()->startOfMonth()->toDateString();
        $this->to   = Carbon::now('Asia/Manila')->toDateString();
        $this->partialDays = $this->defaultPartialDays();
    }

    private function refreshProjectionDefault(): void
    {
        $this->partialDays = $this->defaultPartialDays();
    }

    // Recompute the projection cohort whenever the date range OR the filters change,
    // so the "≥300 (or all available)" default always reflects the current dataset.
    public function updatedFrom(): void            { $this->refreshProjectionDefault(); }
    public function updatedTo(): void              { $this->refreshProjectionDefault(); }
    public function updatedSelectedItems(): void   { $this->refreshProjectionDefault(); }
    public function updatedSelectedSenders(): void { $this->refreshProjectionDefault(); }
    public function updatedSelectedCods(): void    { $this->refreshProjectionDefault(); }

    /**
     * Default projection cutoff: the earliest window [from → D] that reaches at least
     * PROJECTION_MIN shipments. If fewer than that exist, use the whole range.
     */
    private function defaultPartialDays(): int
    {
        if (! $this->from || ! $this->to) {
            return 0;
        }

        [$fromDt, $toDt] = $this->rangeFull();

        // submission_time of the Nth-earliest shipment (N = PROJECTION_MIN).
        $row = $this->filteredQuery($fromDt, $toDt)
            ->orderBy('submission_time')
            ->offset(self::PROJECTION_MIN - 1)
            ->limit(1)
            ->get(['submission_time'])
            ->first();

        if (! $row || empty($row->submission_time)) {
            return $this->totalDays(); // fewer than PROJECTION_MIN → use all available
        }

        $days = Carbon::parse($this->from, 'Asia/Manila')
            ->diffInDays(Carbon::parse($row->submission_time, 'Asia/Manila'));

        return max(0, min($this->totalDays(), $days));
    }

    public function clearFilters(): void
    {
        $this->reset('selectedItems', 'selectedSenders', 'selectedCods');
        $this->refreshProjectionDefault();
        $this->dispatch('rts-filters-updated');
    }

    /** Remove a single selected value (from a chip's ✕). */
    public function removeFilter(string $type, int $index): void
    {
        $prop = ['item' => 'selectedItems', 'sender' => 'selectedSenders', 'cod' => 'selectedCods'][$type] ?? null;
        if (! $prop) {
            return;
        }

        $arr = $this->{$prop};
        if (array_key_exists($index, $arr)) {
            unset($arr[$index]);
            $this->{$prop} = array_values($arr);
        }

        $this->refreshProjectionDefault();
        $this->dispatch('rts-filters-updated');
    }

    private function totalDays(): int
    {
        if (! $this->from || ! $this->to) {
            return 0;
        }

        return max(0, Carbon::parse($this->from, 'Asia/Manila')->diffInDays(Carbon::parse($this->to, 'Asia/Manila')));
    }

    /** Base query: this user + date range only (used for the cascading option lists). */
    private function baseQuery(Carbon $fromDt, Carbon $toDt)
    {
        return DB::table('from_jnts')
            ->where('user_id', auth()->id())
            ->whereBetween('submission_time', [$fromDt, $toDt]);
    }

    /** Base query + the active multi-select filters. */
    private function filteredQuery(Carbon $fromDt, Carbon $toDt)
    {
        return $this->baseQuery($fromDt, $toDt)
            ->when($this->selectedItems, fn ($q) => $q->whereIn('item_name', $this->selectedItems))
            ->when($this->selectedSenders, fn ($q) => $q->whereIn('sender', $this->selectedSenders))
            ->when($this->selectedCods, fn ($q) => $q->whereIn('cod', $this->selectedCods));
    }

    /**
     * Status totals + percentages for one date window (applies the active filters).
     *   - DELIVERED  = status is exactly "Delivered"
     *   - RTS        = "For Return" / "Returned"
     *   - IN TRANSIT = everything else
     */
    private function breakdown(Carbon $fromDt, Carbon $toDt): array
    {
        $row = $this->filteredQuery($fromDt, $toDt)->selectRaw("
            COUNT(*) as quantity,
            SUM(CASE WHEN LOWER(TRIM(status)) = 'delivered' THEN 1 ELSE 0 END) as delivered,
            SUM(CASE WHEN LOWER(status) LIKE '%return%' OR LOWER(status) LIKE '%rts%' THEN 1 ELSE 0 END) as rts
        ")->first();

        $qty = (int) ($row->quantity ?? 0);
        $rts = (int) ($row->rts ?? 0);
        $del = (int) ($row->delivered ?? 0);
        $transit = max(0, $qty - $rts - $del);
        $base = max(1, $qty);

        return [
            'total'          => $qty,
            'totalRts'       => $rts,
            'totalDelivered' => $del,
            'totalTransit'   => $transit,
            'pctRts'         => round($rts / $base * 100, 1),
            'pctDelivered'   => round($del / $base * 100, 1),
            'pctTransit'     => round($transit / $base * 100, 1),
        ];
    }

    /** Cascading option list: distinct values narrowed by the OTHER active filters. */
    private function optionsFor(string $column, string $skipModel): array
    {
        if (! $this->from || ! $this->to) {
            return [];
        }

        [$fromDt, $toDt] = $this->rangeFull();
        $q = $this->baseQuery($fromDt, $toDt);
        if ($skipModel !== 'selectedItems' && $this->selectedItems) {
            $q->whereIn('item_name', $this->selectedItems);
        }
        if ($skipModel !== 'selectedSenders' && $this->selectedSenders) {
            $q->whereIn('sender', $this->selectedSenders);
        }
        if ($skipModel !== 'selectedCods' && $this->selectedCods) {
            $q->whereIn('cod', $this->selectedCods);
        }

        return $q->whereNotNull($column)->where($column, '<>', '')
            ->distinct()->orderBy($column)->pluck($column)->all();
    }

    /** @return array{0: Carbon, 1: Carbon} full selected range */
    private function rangeFull(): array
    {
        return [
            Carbon::parse($this->from, 'Asia/Manila')->startOfDay(),
            Carbon::parse($this->to, 'Asia/Manila')->endOfDay(),
        ];
    }

    /** Per-group table rows for the full range (with active filters). */
    private function results(Carbon $fromDt, Carbon $toDt): array
    {
        $rows = $this->filteredQuery($fromDt, $toDt)->selectRaw("
            COALESCE(sender,'')    as sender,
            COALESCE(item_name,'') as item_name,
            COALESCE(cod,'')       as cod,
            COUNT(*)               as quantity,
            MIN(submission_time)   as min_sub,
            MAX(submission_time)   as max_sub,
            SUM(CASE WHEN LOWER(TRIM(status)) = 'delivered' THEN 1 ELSE 0 END) as delivered_count,
            SUM(CASE WHEN LOWER(status) LIKE '%return%' OR LOWER(status) LIKE '%rts%' THEN 1 ELSE 0 END) as rts_count
        ")->groupBy('sender', 'item_name', 'cod')->get();

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
            $settled      = $rts + $delivered;

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
                'current_rts'       => $settled > 0 ? round($rts / $settled * 100, 2) : null,
            ];
        })->sortByDesc('rts_percent')->values()->all();
    }

    public function render()
    {
        if (! $this->from || ! $this->to) {
            return view('livewire.rts-monitor', ['results' => [], 'totalDays' => 0]);
        }

        [$fromDt, $toDt] = $this->rangeFull();

        // Projection window: from → (from + partialDays).
        $totalDays = $this->totalDays();
        $days      = max(0, min($totalDays, (int) ($this->partialDays ?? $totalDays)));
        $partialTo = Carbon::parse($this->from, 'Asia/Manila')->addDays($days)->endOfDay();

        return view('livewire.rts-monitor', [
            'results'       => $this->results($fromDt, $toDt),
            'itemOptions'   => $this->optionsFor('item_name', 'selectedItems'),
            'senderOptions' => $this->optionsFor('sender', 'selectedSenders'),
            'codOptions'    => $this->optionsFor('cod', 'selectedCods'),
            'activeFilters' => count($this->selectedItems) + count($this->selectedSenders) + count($this->selectedCods),

            'full'          => $this->breakdown($fromDt, $toDt),
            'projection'    => $this->breakdown($fromDt, $partialTo),
            'totalDays'     => $totalDays,
            'partialDate'   => $partialTo->toDateString(),
        ]);
    }
}
