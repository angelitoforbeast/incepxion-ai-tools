<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-xl font-bold text-slate-900 mb-1">Admin</h1>
    @include('partials.admin-nav')

    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Profit Calculator — History</h2>
            <p class="text-sm text-slate-500">Net-profit and adjustment runs. Sort any column; filter users / number ranges in the header.</p>
        </div>
        <div class="flex items-end gap-3">
            <div>
                <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wide mb-1">Type</label>
                <select wire:model.live="type" class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All</option>
                    <option value="net">Net profit</option>
                    <option value="adjustment">Adjustment</option>
                </select>
            </div>
            @if ($activeFilters)
                <button wire:click="clearFilters" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                    Clear ({{ $activeFilters }})
                </button>
            @endif
        </div>
    </div>

    @php
        $columns = [
            ['key' => 'created_at',        'label' => 'When',     'type' => 'plain', 'align' => 'left'],
            ['key' => 'user_id',           'label' => 'User',     'type' => 'user',  'align' => 'left'],
            ['key' => 'type',              'label' => 'Type',     'type' => 'plain', 'align' => 'left'],
            ['key' => 'cpp',               'label' => 'CPP',      'type' => 'num',   'align' => 'right'],
            ['key' => 'cogs',              'label' => 'COGS',     'type' => 'num',   'align' => 'right'],
            ['key' => 'shipping_fee',      'label' => 'Ship',     'type' => 'num',   'align' => 'right'],
            ['key' => 'orders',            'label' => 'Orders',   'type' => 'num',   'align' => 'right'],
            ['key' => 'cod_price',         'label' => 'COD',      'type' => 'num',   'align' => 'right'],
            ['key' => 'cod_fee',           'label' => 'COD Fee',  'type' => 'num',   'align' => 'right'],
            ['key' => 'rts',               'label' => 'RTS',      'type' => 'num',   'align' => 'right'],
            ['key' => 'net_profit',        'label' => 'Net',      'type' => 'num',   'align' => 'right'],
            ['key' => 'target_net_profit', 'label' => 'Target',   'type' => 'num',   'align' => 'right'],
            ['key' => 'suggested_rts',     'label' => 'Sug. RTS', 'type' => 'num',   'align' => 'right'],
            ['key' => 'suggested_cpp',     'label' => 'Sug. CPP', 'type' => 'num',   'align' => 'right'],
        ];
        $numTrim = fn ($v) => $v === null ? '—' : rtrim(rtrim(number_format($v, 4), '0'), '.');
    @endphp

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        @foreach ($columns as $col)
                            @php
                                $isSorted = $sortBy === $col['key'];
                                $filtered = $col['type'] === 'user'
                                    ? count($selectedUsers) > 0
                                    : ($col['type'] === 'num' && (is_numeric($min[$col['key']] ?? null) || is_numeric($max[$col['key']] ?? null)));
                            @endphp
                            <th class="px-3 py-2 font-medium whitespace-nowrap {{ $col['align'] === 'right' ? 'text-right' : 'text-left' }}">
                                <div class="inline-flex items-center gap-1 {{ $col['align'] === 'right' ? 'flex-row-reverse' : '' }}">
                                    <button type="button" wire:click="sort('{{ $col['key'] }}')" class="inline-flex items-center gap-1 hover:text-indigo-600">
                                        {{ $col['label'] }}
                                        @if ($isSorted)
                                            <span class="text-indigo-600">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>
                                        @else
                                            <span class="text-slate-300">↕</span>
                                        @endif
                                    </button>

                                    @if ($col['type'] === 'user')
                                        <div x-data="{ open: false, s: '' }" class="relative">
                                            <button type="button" @click="open = !open" class="rounded p-0.5 {{ $filtered ? 'text-indigo-600' : 'text-slate-400 hover:text-slate-600' }}">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v1.586a1 1 0 01-.293.707L12 11.414V16a1 1 0 01-1.447.894l-2-1A1 1 0 018 15v-3.586L3.293 6.293A1 1 0 013 5.586V4z"/></svg>
                                            </button>
                                            <div x-show="open" x-transition @click.outside="open = false" style="display:none"
                                                 class="absolute z-40 mt-1 left-0 w-64 bg-white border border-slate-200 rounded-lg shadow-lg p-2 font-normal normal-case">
                                                <input x-model="s" type="text" placeholder="Search user…"
                                                       class="w-full border border-slate-300 rounded p-1.5 text-xs mb-2 focus:border-indigo-500 focus:ring-indigo-500">
                                                <div class="max-h-56 overflow-y-auto space-y-0.5 pr-1">
                                                    @forelse ($users as $u)
                                                        <label class="flex items-center gap-2 text-xs text-slate-700 hover:bg-slate-50 rounded px-1.5 py-1 cursor-pointer"
                                                               x-show="s === '' || (@js(mb_strtolower($u->name.' '.$u->email))).includes(s.toLowerCase())">
                                                            <input type="checkbox" value="{{ $u->id }}" wire:model.live="selectedUsers" class="rounded border-slate-300 text-indigo-600">
                                                            <span class="truncate" title="{{ $u->email }}">{{ $u->name }} <span class="text-slate-400">· {{ $u->email }}</span></span>
                                                        </label>
                                                    @empty
                                                        <p class="text-xs text-slate-400 px-1 py-2">No users.</p>
                                                    @endforelse
                                                </div>
                                            </div>
                                        </div>
                                    @elseif ($col['type'] === 'num')
                                        <div x-data="{ open: false }" class="relative">
                                            <button type="button" @click="open = !open" class="rounded p-0.5 {{ $filtered ? 'text-indigo-600' : 'text-slate-400 hover:text-slate-600' }}">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v1.586a1 1 0 01-.293.707L12 11.414V16a1 1 0 01-1.447.894l-2-1A1 1 0 018 15v-3.586L3.293 6.293A1 1 0 013 5.586V4z"/></svg>
                                            </button>
                                            <div x-show="open" x-transition @click.outside="open = false" style="display:none"
                                                 class="absolute z-40 mt-1 right-0 w-40 bg-white border border-slate-200 rounded-lg shadow-lg p-2 font-normal normal-case space-y-2">
                                                <div>
                                                    <label class="block text-[10px] font-semibold text-slate-400 uppercase mb-0.5">Min</label>
                                                    <input type="number" step="any" wire:model.live.debounce.400ms="min.{{ $col['key'] }}" class="no-spinner w-full border border-slate-300 rounded p-1 text-xs">
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] font-semibold text-slate-400 uppercase mb-0.5">Max</label>
                                                    <input type="number" step="any" wire:model.live.debounce.400ms="max.{{ $col['key'] }}" class="no-spinner w-full border border-slate-300 rounded p-1 text-xs">
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rows as $r)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-1.5 whitespace-nowrap text-slate-600">{{ $r->created_at?->timezone('Asia/Manila')->format('M j, g:i A') }}</td>
                            <td class="px-3 py-1.5 text-slate-800 max-w-[200px] truncate" title="{{ $r->user?->email }}">{{ $r->user?->email ?? '—' }}</td>
                            <td class="px-3 py-1.5">
                                <span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold uppercase {{ $r->type === 'adjustment' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800' }}">{{ $r->type }}</span>
                            </td>
                            <td class="px-3 py-1.5 text-right tabular-nums">{{ number_format($r->cpp, 2) }}</td>
                            <td class="px-3 py-1.5 text-right tabular-nums">{{ number_format($r->cogs, 2) }}</td>
                            <td class="px-3 py-1.5 text-right tabular-nums">{{ number_format($r->shipping_fee, 2) }}</td>
                            <td class="px-3 py-1.5 text-right tabular-nums">{{ number_format($r->orders) }}</td>
                            <td class="px-3 py-1.5 text-right tabular-nums">{{ number_format($r->cod_price, 2) }}</td>
                            <td class="px-3 py-1.5 text-right tabular-nums">{{ $numTrim($r->cod_fee) }}</td>
                            <td class="px-3 py-1.5 text-right tabular-nums">{{ $numTrim($r->rts) }}</td>
                            <td class="px-3 py-1.5 text-right tabular-nums font-semibold {{ $r->net_profit < 0 ? 'text-red-600' : 'text-indigo-700' }}">₱{{ number_format($r->net_profit, 2) }}</td>
                            <td class="px-3 py-1.5 text-right tabular-nums text-slate-600">{{ $r->target_net_profit !== null ? '₱'.number_format($r->target_net_profit, 2) : '—' }}</td>
                            <td class="px-3 py-1.5 text-right tabular-nums {{ $r->suggested_rts !== null ? 'text-emerald-700' : 'text-slate-300' }}">{{ $numTrim($r->suggested_rts) }}</td>
                            <td class="px-3 py-1.5 text-right tabular-nums {{ $r->suggested_cpp !== null ? 'text-emerald-700' : 'text-slate-300' }}">{{ $r->suggested_cpp !== null ? '₱'.number_format($r->suggested_cpp, 2) : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($columns) }}" class="px-3 py-8 text-center text-slate-400">No calculations match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $rows->links() }}</div>

    <style>
        .no-spinner::-webkit-outer-spin-button,
        .no-spinner::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        .no-spinner { -moz-appearance: textfield; appearance: textfield; }
    </style>
</div>
