<div>
    @include('partials.rts-nav')

    <div class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6" x-data="{ q: '' }">

        {{-- Filters --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-4">
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">From</label>
                    <input type="date" wire:model.live="from" class="border border-gray-300 rounded-lg p-2 text-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">To</label>
                    <input type="date" wire:model.live="to" class="border border-gray-300 rounded-lg p-2 text-sm">
                </div>

                @php
                    $filters = [
                        ['label' => 'Item Name', 'model' => 'selectedItems',   'options' => $itemOptions,   'selected' => $selectedItems],
                        ['label' => 'Sender',    'model' => 'selectedSenders', 'options' => $senderOptions, 'selected' => $selectedSenders],
                        ['label' => 'COD',       'model' => 'selectedCods',    'options' => $codOptions,    'selected' => $selectedCods],
                    ];
                @endphp

                @foreach ($filters as $f)
                    <div wire:key="filt-{{ $f['model'] }}">
                        <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">{{ $f['label'] }}</label>
                        @php $selCount = count($f['selected']); @endphp
                        <div x-data="{ open: false, s: '' }" class="relative">
                            <button type="button" @click="open = !open"
                                    class="flex items-center gap-2 border rounded-lg px-3 py-2 text-sm bg-white hover:bg-gray-50 min-w-[150px] justify-between {{ $selCount ? 'border-indigo-400 text-indigo-700 font-medium' : 'border-gray-300 text-gray-700' }}">
                                <span>{{ $selCount ? $selCount.' selected' : 'All' }}</span>
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-transition @click.outside="open = false" style="display:none"
                                 class="absolute z-30 mt-1 w-64 bg-white border border-gray-200 rounded-lg shadow-lg p-2">
                                <input x-model="s" type="text" placeholder="Filter {{ strtolower($f['label']) }}…"
                                       class="w-full border border-gray-300 rounded p-1.5 text-xs mb-2 focus:border-indigo-500 focus:ring-indigo-500">
                                <div class="max-h-56 overflow-y-auto space-y-0.5 pr-1">
                                    @forelse ($f['options'] as $opt)
                                        <label wire:key="opt-{{ $f['model'] }}-{{ md5((string) $opt) }}"
                                               class="flex items-center gap-2 text-xs text-gray-700 hover:bg-gray-50 rounded px-1.5 py-1 cursor-pointer"
                                               x-show="s === '' || (@js(mb_strtolower((string) $opt))).includes(s.toLowerCase())">
                                            <input type="checkbox" value="{{ $opt }}" wire:model.live="{{ $f['model'] }}"
                                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            <span class="truncate" title="{{ $opt }}">{{ $opt }}</span>
                                        </label>
                                    @empty
                                        <p class="text-xs text-gray-400 px-1 py-2">No values in this range.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                @if ($activeFilters)
                    <button wire:click="clearFilters"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50">
                        Clear filters ({{ $activeFilters }})
                    </button>
                @endif

                <div class="flex-1"></div>
                <div>
                    <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">Search table</label>
                    <input type="text" x-model="q" placeholder="Search sender / item…" class="border border-gray-300 rounded-lg p-2 text-sm min-w-[200px]">
                </div>
            </div>
            @if ($activeFilters)
                <div class="mt-3 pt-3 border-t border-gray-100 flex flex-wrap items-center gap-2">
                    <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Active</span>
                    @php
                        $chipGroups = [
                            ['type' => 'item',   'values' => $selectedItems,   'cls' => 'bg-teal-50 border-teal-200 text-teal-700',   'x' => 'text-teal-400 hover:bg-teal-100'],
                            ['type' => 'sender', 'values' => $selectedSenders, 'cls' => 'bg-blue-50 border-blue-200 text-blue-700',   'x' => 'text-blue-400 hover:bg-blue-100'],
                            ['type' => 'cod',    'values' => $selectedCods,    'cls' => 'bg-amber-50 border-amber-200 text-amber-700', 'x' => 'text-amber-500 hover:bg-amber-100'],
                        ];
                    @endphp
                    @foreach ($chipGroups as $g)
                        @foreach ($g['values'] as $i => $v)
                            <span class="inline-flex items-center gap-1 rounded-full border pl-2.5 pr-1 py-0.5 text-xs max-w-[240px] {{ $g['cls'] }}">
                                <span class="truncate" title="{{ ucfirst($g['type']) }}: {{ $v }}">{{ $v }}</span>
                                <button type="button" wire:click="removeFilter('{{ $g['type'] }}', {{ $i }})"
                                        class="flex-shrink-0 w-4 h-4 rounded-full inline-flex items-center justify-center leading-none {{ $g['x'] }}">&times;</button>
                            </span>
                        @endforeach
                    @endforeach
                    <button type="button" wire:click="clearFilters" class="ml-1 text-xs font-semibold text-gray-500 hover:text-gray-700 underline">Clear all</button>
                </div>
            @else
                <p class="mt-2 text-[11px] text-gray-400">Tip: filters cascade instantly — checking a value immediately narrows the other dropdowns.</p>
            @endif
        </div>

        {{-- Two charts: RTS Projection (partial) + Full range --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">

            {{-- Chart 1: RTS Projection (slideable partial end date) --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <div class="flex items-center justify-between mb-1">
                    <h2 class="text-sm font-semibold text-gray-800">🔮 RTS Projection</h2>
                    <span class="text-[10px] font-bold uppercase tracking-wide text-amber-700 bg-amber-100 rounded-full px-2 py-0.5">Partial cohort</span>
                </div>
                <p class="text-xs text-gray-400 mb-3">Older shipments are already settled, so their RTS% projects where the full period is headed.</p>

                <div class="mb-4">
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="text-gray-500">Data up to</span>
                        <span class="font-semibold text-gray-800">{{ \Carbon\Carbon::parse($from)->format('M j') }} → {{ \Carbon\Carbon::parse($partialDate)->format('M j, Y') }}</span>
                    </div>
                    <input type="range" min="0" max="{{ max(1, $totalDays) }}" wire:model.live.debounce.400ms="partialDays"
                           @if ($totalDays === 0) disabled @endif
                           class="w-full accent-indigo-600 cursor-pointer">
                    <div class="flex justify-between text-[10px] text-gray-400 mt-1">
                        <span>{{ \Carbon\Carbon::parse($from)->format('M j') }}</span>
                        <span>{{ \Carbon\Carbon::parse($to)->format('M j') }}</span>
                    </div>
                </div>

                @include('partials.rts-pie', $projection)
            </div>

            {{-- Chart 2: Full selected range --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <div class="flex items-center justify-between mb-1">
                    <h2 class="text-sm font-semibold text-gray-800">📊 Full Range</h2>
                    <span class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">{{ \Carbon\Carbon::parse($from)->format('M j') }} → {{ \Carbon\Carbon::parse($to)->format('M j, Y') }}{{ $activeFilters ? ' · filtered' : '' }}</span>
                </div>
                <p class="text-xs text-gray-400 mb-3">All shipments in the selected date range (includes those still in transit).</p>
                <div class="mt-[52px]">
                    @include('partials.rts-pie', $full)
                </div>
            </div>
        </div>
        <p class="text-[11px] text-gray-400 mb-4">
            DELIVERED = status is exactly "Delivered" · RTS = "For Return" / "Returned" · IN TRANSIT = all other statuses.
        </p>

        {{-- Per-group table --}}
        @if (count($results))
            <div class="border border-gray-200 rounded-xl shadow-sm overflow-hidden bg-white">
                <div class="overflow-auto" style="max-height:calc(100vh - 500px);min-height:200px;">
                    <table class="min-w-full text-xs">
                        <thead class="bg-slate-100 text-gray-600 sticky top-0">
                            <tr>
                                <th class="text-left px-3 py-2 font-semibold uppercase tracking-wide whitespace-nowrap">Date Range</th>
                                <th class="text-left px-3 py-2 font-semibold uppercase tracking-wide whitespace-nowrap">Sender</th>
                                <th class="text-left px-3 py-2 font-semibold uppercase tracking-wide whitespace-nowrap">Item</th>
                                <th class="text-left px-3 py-2 font-semibold uppercase tracking-wide whitespace-nowrap">COD</th>
                                <th class="text-right px-3 py-2 font-semibold uppercase tracking-wide">Qty</th>
                                <th class="text-right px-3 py-2 font-semibold uppercase tracking-wide">RTS</th>
                                <th class="text-right px-3 py-2 font-semibold uppercase tracking-wide">Del</th>
                                <th class="text-right px-3 py-2 font-semibold uppercase tracking-wide">Transit</th>
                                <th class="text-right px-3 py-2 font-semibold uppercase tracking-wide">RTS%</th>
                                <th class="text-right px-3 py-2 font-semibold uppercase tracking-wide">Del%</th>
                                <th class="text-right px-3 py-2 font-semibold uppercase tracking-wide">Transit%</th>
                                <th class="text-right px-3 py-2 font-semibold uppercase tracking-wide">Cur RTS%</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($results as $r)
                                @php
                                    $rtsColorFor = fn ($p) => $p > 25 ? 'bg-red-100'
                                        : ($p > 20 ? 'bg-orange-100'
                                        : ($p > 15 ? 'bg-green-100' : 'bg-cyan-50'));
                                    $rtsColor = $rtsColorFor($r['rts_percent']);
                                    $curColor = is_numeric($r['current_rts']) ? $rtsColorFor($r['current_rts']) : '';
                                @endphp
                                <tr class="hover:bg-blue-50" x-show="q === '' || (@js(mb_strtolower($r['sender'].' '.$r['item'].' '.$r['cod']))).includes(q.toLowerCase())">
                                    <td class="px-3 py-1.5 whitespace-nowrap text-gray-600">{{ $r['date_range'] }}</td>
                                    <td class="px-3 py-1.5 whitespace-nowrap font-medium text-gray-800">{{ $r['sender'] }}</td>
                                    <td class="px-3 py-1.5 whitespace-nowrap text-gray-700">{{ $r['item'] }}</td>
                                    <td class="px-3 py-1.5 whitespace-nowrap text-gray-700">{{ $r['cod'] }}</td>
                                    <td class="px-3 py-1.5 text-right text-gray-700">{{ number_format($r['quantity']) }}</td>
                                    <td class="px-3 py-1.5 text-right" style="color:#b91c1c;">{{ number_format($r['rts_count']) }}</td>
                                    <td class="px-3 py-1.5 text-right" style="color:#15803d;">{{ number_format($r['delivered_count']) }}</td>
                                    <td class="px-3 py-1.5 text-right" style="color:#1d4ed8;">{{ number_format($r['transit_count']) }}</td>
                                    <td class="px-3 py-1.5 text-right font-semibold {{ $rtsColor }}">{{ number_format($r['rts_percent'], 2) }}%</td>
                                    <td class="px-3 py-1.5 text-right text-gray-700">{{ number_format($r['delivered_percent'], 2) }}%</td>
                                    <td class="px-3 py-1.5 text-right text-gray-700">{{ number_format($r['transit_percent'], 2) }}%</td>
                                    <td class="px-3 py-1.5 text-right font-semibold {{ $curColor }}">{{ is_numeric($r['current_rts']) ? number_format($r['current_rts'], 2).'%' : 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <p class="mt-2 text-xs text-gray-500">{{ count($results) }} groups · sorted by RTS% (highest first)</p>
        @else
            <div class="rounded-xl border-2 border-dashed border-gray-200 text-gray-400 text-center p-12">
                No data for these filters. Widen the date range or clear the filters.
            </div>
        @endif
    </div>
</div>
