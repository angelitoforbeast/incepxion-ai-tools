<?php

namespace App\Livewire;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('RTS Monitoring')]
class RtsMonitor extends Component
{
    public string $from = '';
    public string $to = '';

    /** Multi-select filters (empty = all). */
    public array $selectedItems = [];
    public array $selectedSenders = [];
    public array $selectedCods = [];

    /** Projection window — independently adjustable inside the selected range. */
    public string $projFrom = '';
    public string $projTo   = '';

    /** Slider position: days from $projFrom. Kept in step with $projTo. */
    public ?int $partialDays = null;

    /**
     * A cohort counts as settled once fewer than this share of it is still in transit —
     * at that point almost nothing is left to change, so its RTS% is effectively final.
     */
    private const SETTLED_MAX_TRANSIT_PCT = 1.0;

    public function mount(): void
    {
        // Default: this month, up to today (by submission_time / pickup date).
        $this->from = Carbon::now('Asia/Manila')->startOfMonth()->toDateString();
        $this->to   = Carbon::now('Asia/Manila')->toDateString();
        $this->refreshProjectionDefault();
    }

    private function refreshProjectionDefault(): void
    {
        $this->projFrom = $this->from;
        $this->projTo   = $this->defaultProjectionEnd();
        $this->syncPartialDays();
    }

    /** Slider position follows the dates. */
    private function syncPartialDays(): void
    {
        $this->partialDays = max(0, Carbon::parse($this->projFrom, 'Asia/Manila')
            ->diffInDays(Carbon::parse($this->projTo, 'Asia/Manila')));
    }

    // Recompute the projection window whenever the date range OR the filters change,
    // so the settled-cohort default always reflects the current dataset.
    public function updatedFrom(): void            { $this->refreshProjectionDefault(); }
    public function updatedTo(): void              { $this->refreshProjectionDefault(); }
    public function updatedSelectedItems(): void   { $this->refreshProjectionDefault(); }
    public function updatedSelectedSenders(): void { $this->refreshProjectionDefault(); }
    public function updatedSelectedCods(): void    { $this->refreshProjectionDefault(); }

    /** Dragging the slider moves the projection end. */
    public function updatedPartialDays(): void
    {
        $days = max(0, min($this->projectionSpan(), (int) $this->partialDays));
        $this->partialDays = $days;
        $this->projTo = Carbon::parse($this->projFrom, 'Asia/Manila')->addDays($days)->toDateString();
    }

    /** Typing a projection start: clamp into the selected range, keep from <= to. */
    public function updatedProjFrom(): void
    {
        $this->projFrom = $this->clampToRange($this->projFrom);
        if ($this->projTo < $this->projFrom) {
            $this->projTo = $this->projFrom;
        }
        $this->syncPartialDays();
    }

    /** Typing a projection end: clamp into the selected range, keep to >= from. */
    public function updatedProjTo(): void
    {
        $this->projTo = $this->clampToRange($this->projTo);
        if ($this->projTo < $this->projFrom) {
            $this->projFrom = $this->projTo;
        }
        $this->syncPartialDays();
    }

    private function clampToRange(string $date): string
    {
        if (! $date) {
            return $this->from;
        }

        return max($this->from, min($this->to, $date));
    }

    /** Days the slider can travel: projection start → end of the selected range. */
    private function projectionSpan(): int
    {
        if (! $this->projFrom || ! $this->to) {
            return 0;
        }

        return max(0, Carbon::parse($this->projFrom, 'Asia/Manila')
            ->diffInDays(Carbon::parse($this->to, 'Asia/Manila')));
    }

    /**
     * Latest day whose cohort [from → day] is still settled, i.e. under
     * SETTLED_MAX_TRANSIT_PCT in transit. That's the largest cohort whose RTS% can be
     * trusted. Falls back to the first day when nothing has settled yet.
     */
    private function defaultProjectionEnd(): string
    {
        if (! $this->from || ! $this->to) {
            return $this->to ?: $this->from;
        }

        [$fromDt, $toDt] = $this->rangeFull();

        $rows = $this->filteredQuery($fromDt, $toDt)->selectRaw("
            DATE(submission_time) as d,
            COUNT(*) as total,
            SUM(CASE WHEN LOWER(TRIM(status)) = 'delivered' THEN 1 ELSE 0 END) as delivered,
            SUM(CASE WHEN LOWER(status) LIKE '%return%' OR LOWER(status) LIKE '%rts%' THEN 1 ELSE 0 END) as rts
        ")->groupBy('d')->orderBy('d')->get();

        if ($rows->isEmpty()) {
            return $this->to;
        }

        $cumTotal = 0;
        $cumSettled = 0;
        $best = null;

        foreach ($rows as $r) {
            $cumTotal   += (int) $r->total;
            $cumSettled += (int) $r->delivered + (int) $r->rts;

            $transitPct = $cumTotal > 0 ? (($cumTotal - $cumSettled) / $cumTotal) * 100 : 100;
            if ($transitPct < self::SETTLED_MAX_TRANSIT_PCT) {
                $best = substr((string) $r->d, 0, 10);
            }
        }

        return $best ?? substr((string) $rows->first()->d, 0, 10);
    }

    public function clearFilters(): void
    {
        $this->reset('selectedItems', 'selectedSenders', 'selectedCods');
        $this->refreshProjectionDefault();
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
            ];
        })->sortByDesc('rts_percent')->values()->all();
    }

    public function render()
    {
        if (! $this->from || ! $this->to) {
            return view('livewire.rts-monitor', ['activeRtsTab' => 'tools.rts.monitor', 'results' => [], 'totalDays' => 0]);
        }

        [$fromDt, $toDt] = $this->rangeFull();

        // Projection window is its own range inside the selected one.
        if (! $this->projFrom || ! $this->projTo) {
            $this->refreshProjectionDefault();
        }

        $projFromDt = Carbon::parse($this->projFrom, 'Asia/Manila')->startOfDay();
        $projToDt   = Carbon::parse($this->projTo, 'Asia/Manila')->endOfDay();
        $projection = $this->breakdown($projFromDt, $projToDt);

        // RTS share of the shipments that have actually finished — ignores whatever is
        // still moving, so it doesn't get diluted by an unsettled tail.
        $settled = $projection['totalRts'] + $projection['totalDelivered'];
        $projection['estimatedRts'] = $settled > 0
            ? round($projection['totalRts'] / $settled * 100, 1)
            : null;

        return view('livewire.rts-monitor', [
            'activeRtsTab'  => 'tools.rts.monitor',
            'results'       => $this->results($fromDt, $toDt),
            'itemOptions'   => $this->optionsFor('item_name', 'selectedItems'),
            'senderOptions' => $this->optionsFor('sender', 'selectedSenders'),
            'codOptions'    => $this->optionsFor('cod', 'selectedCods'),
            'activeFilters' => count($this->selectedItems) + count($this->selectedSenders) + count($this->selectedCods),

            'full'          => $this->breakdown($fromDt, $toDt),
            'projection'    => $projection,
            'totalDays'     => $this->totalDays(),
            'projSpan'      => $this->projectionSpan(),
        ]);
    }
}
