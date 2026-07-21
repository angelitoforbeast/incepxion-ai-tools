<div>
    @include('partials.rts-nav')

    <div class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6" x-data="{ q: '' }">

        {{-- Filters --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-4">
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">From</label>
                    <input type="date" wire:model="from" class="border border-gray-300 rounded-lg p-2 text-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">To</label>
                    <input type="date" wire:model="to" class="border border-gray-300 rounded-lg p-2 text-sm">
                </div>

                @php
                    $filters = [
                        ['label' => 'Item Name', 'model' => 'selectedItems',   'options' => $itemOptions,   'selected' => $selectedItems],
                        ['label' => 'Sender',    'model' => 'selectedSenders', 'options' => $senderOptions, 'selected' => $selectedSenders],
                        ['label' => 'COD',       'model' => 'selectedCods',    'options' => $codOptions,    'selected' => $selectedCods],
                    ];
                @endphp

                @foreach ($filters as $f)
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">{{ $f['label'] }}</label>
                        <div x-data="{ open: false, s: '' }" class="relative">
                            <button type="button" @click="open = !open"
                                    class="flex items-center gap-2 border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white hover:bg-gray-50 min-w-[150px] justify-between">
                                <span class="text-gray-700">
                                    {{ count($f['selected']) ? count($f['selected']).' selected' : 'All' }}
                                </span>
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-transition @click.outside="open = false" style="display:none"
                                 class="absolute z-30 mt-1 w-64 bg-white border border-gray-200 rounded-lg shadow-lg p-2">
                                <input x-model="s" type="text" placeholder="Filter {{ strtolower($f['label']) }}…"
                                       class="w-full border border-gray-300 rounded p-1.5 text-xs mb-2 focus:border-indigo-500 focus:ring-indigo-500">
                                <div class="max-h-56 overflow-y-auto space-y-0.5 pr-1">
                                    @forelse ($f['options'] as $opt)
                                        <label class="flex items-center gap-2 text-xs text-gray-700 hover:bg-gray-50 rounded px-1.5 py-1 cursor-pointer"
                                               x-show="s === '' || (@js(mb_strtolower((string) $opt))).includes(s.toLowerCase())">
                                            <input type="checkbox" value="{{ $opt }}" wire:model="{{ $f['model'] }}"
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

                <button wire:click="$refresh"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Apply</button>

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
            <p class="mt-2 text-[11px] text-gray-400">Tip: filters cascade — after Apply, each dropdown only shows values that match your other selections.</p>
        </div>

        {{-- Pie chart summary --}}
        @php $stop2 = $pctRts + $pctDelivered; @endphp
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-4">
            <h2 class="text-sm font-semibold text-gray-800 mb-4">Status Breakdown <span class="text-xs font-normal text-gray-400">({{ number_format($totalQty) }} shipments{{ $activeFilters ? ' · filtered' : '' }})</span></h2>
            <div class="flex flex-wrap items-center gap-8">
                <div class="relative" style="width:170px;height:170px;flex-shrink:0;">
                    <div style="width:170px;height:170px;border-radius:9999px;background:conic-gradient(#dc2626 0 {{ $pctRts }}%, #16a34a {{ $pctRts }}% {{ $stop2 }}%, #2563eb {{ $stop2 }}% 100%);"></div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div style="width:96px;height:96px;background:#fff;border-radius:9999px;" class="flex flex-col items-center justify-center shadow-inner">
                            <span class="text-xl font-bold text-gray-900">{{ number_format($totalQty) }}</span>
                            <span class="text-[10px] text-gray-400 uppercase tracking-wide">Total</span>
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <span class="w-3.5 h-3.5 rounded-sm" style="background:#dc2626;"></span>
                        <span class="text-sm text-gray-700 w-24">RTS</span>
                        <span class="text-lg font-bold text-red-600">{{ number_format($pctRts, 1) }}%</span>
                        <span class="text-xs text-gray-400">({{ number_format($totalRts) }})</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="w-3.5 h-3.5 rounded-sm" style="background:#16a34a;"></span>
                        <span class="text-sm text-gray-700 w-24">Delivered</span>
                        <span class="text-lg font-bold text-green-600">{{ number_format($pctDelivered, 1) }}%</span>
                        <span class="text-xs text-gray-400">({{ number_format($totalDelivered) }})</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="w-3.5 h-3.5 rounded-sm" style="background:#2563eb;"></span>
                        <span class="text-sm text-gray-700 w-24">In Transit</span>
                        <span class="text-lg font-bold text-blue-600">{{ number_format($pctTransit, 1) }}%</span>
                        <span class="text-xs text-gray-400">({{ number_format($totalTransit) }})</span>
                    </div>
                </div>
            </div>
            <p class="mt-4 text-[11px] text-gray-400">
                DELIVERED = status is exactly "Delivered" · RTS = "For Return" / "Returned" · IN TRANSIT = all other statuses.
            </p>
        </div>

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
                                    $rtsColor = $r['rts_percent'] > 25 ? 'bg-red-100'
                                              : ($r['rts_percent'] > 20 ? 'bg-orange-100'
                                              : ($r['rts_percent'] > 15 ? 'bg-green-100' : 'bg-cyan-50'));
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
                                    <td class="px-3 py-1.5 text-right text-gray-700">{{ is_numeric($r['current_rts']) ? number_format($r['current_rts'], 2).'%' : 'N/A' }}</td>
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
