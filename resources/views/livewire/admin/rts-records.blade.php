@php
    // One arrow renderer for every sortable header.
    $sortIcon = function (string $col) use ($sortBy, $sortDir) {
        if ($sortBy !== $col) {
            return '<span class="text-slate-300 group-hover:text-slate-400">↕</span>';
        }

        return '<span class="text-indigo-600">'.($sortDir === 'asc' ? '↑' : '↓').'</span>';
    };
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-xl font-bold text-slate-900 mb-1">Admin</h1>
    @include('partials.admin-nav')

    <div class="mb-4">
        <h2 class="text-lg font-semibold text-slate-900">RTS Records</h2>
        <p class="text-sm text-slate-500">Every imported shipment row, across all users.</p>
    </div>

    {{-- Filters --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-4 mb-4">
        <div class="flex flex-wrap items-end gap-3">
            <div class="min-w-[190px]">
                <label class="block text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-1">User</label>
                <select wire:model.live="userId" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All users</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}">#{{ $u->id }} · {{ $u->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-[150px]">
                <label class="block text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-1">Status</label>
                <select wire:model.live="status" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s }}">{{ $s }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-40">
                <label class="block text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-1">From</label>
                <x-date-field model="from" size="text-sm" class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
            </div>

            <div class="w-40">
                <label class="block text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-1">To</label>
                <x-date-field model="to" size="text-sm" class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
            </div>

            <div class="flex-1 min-w-[220px]">
                <label class="block text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-1">Search</label>
                <div class="relative">
                    <input type="text" wire:model.live.debounce.400ms="search" placeholder="Waybill number or item name…"
                           class="w-full rounded-lg border-slate-300 text-sm pr-8 focus:border-indigo-500 focus:ring-indigo-500">
                    <span wire:loading wire:target="search" class="absolute inset-y-0 right-2 flex items-center text-xs text-slate-400">…</span>
                </div>
            </div>

            @if ($this->activeFilters)
                <button wire:click="clearFilters"
                        class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                    Clear ({{ $this->activeFilters }})
                </button>
            @endif
        </div>
    </div>

    {{-- Results --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
            <p class="text-sm text-slate-600">
                @if ($rows->total() > 0)
                    Showing <span class="font-semibold text-slate-900">{{ number_format($rows->firstItem()) }}–{{ number_format($rows->lastItem()) }}</span>
                    of <span class="font-semibold text-slate-900">{{ number_format($rows->total()) }}</span>
                @else
                    No matching rows
                @endif
            </p>
            <div class="flex items-center gap-2">
                <label class="text-xs text-slate-500">Rows</label>
                <select wire:model.live="perPage" class="rounded-lg border-slate-300 py-1 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach ([25, 50, 100, 200] as $n)
                        <option value="{{ $n }}">{{ $n }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- max-h + sticky thead: the header stays put while the rows scroll under it. --}}
        <div class="overflow-auto max-h-[65vh] relative" wire:loading.class="opacity-50">
            <table class="min-w-full text-sm">
                <thead class="sticky top-0 z-10">
                    <tr class="text-left text-[11px] uppercase tracking-wide text-slate-500">
                        @php
                            $cols = [
                                ['key' => 'user_id',         'label' => 'User',    'align' => 'text-left'],
                                ['key' => 'waybill_number',  'label' => 'Waybill', 'align' => 'text-left'],
                                ['key' => 'submission_time', 'label' => 'Pickup',  'align' => 'text-left'],
                                ['key' => 'item_name',       'label' => 'Item',    'align' => 'text-left'],
                                ['key' => 'cod',             'label' => 'COD',     'align' => 'text-right'],
                                ['key' => 'status',          'label' => 'Status',  'align' => 'text-left'],
                            ];
                        @endphp
                        @foreach ($cols as $c)
                            <th class="bg-slate-100 border-b border-slate-200 px-4 py-2.5 font-semibold {{ $c['align'] }}">
                                <button wire:click="sort('{{ $c['key'] }}')" class="group inline-flex items-center gap-1 hover:text-slate-900">
                                    <span>{{ $c['label'] }}</span>
                                    {!! $sortIcon($c['key']) !!}
                                </button>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rows as $r)
                        @php
                            $s = mb_strtolower(trim((string) $r->status));
                            $badge = $s === 'delivered'
                                ? 'bg-green-100 text-green-800'
                                : ((str_contains($s, 'return') || str_contains($s, 'rts'))
                                    ? 'bg-red-100 text-red-700'
                                    : 'bg-blue-100 text-blue-800');
                        @endphp
                        <tr class="hover:bg-indigo-50/40">
                            <td class="px-4 py-2 text-slate-500 whitespace-nowrap">#{{ $r->user_id }}</td>
                            <td class="px-4 py-2 font-mono text-xs text-slate-800 whitespace-nowrap">{{ $r->waybill_number }}</td>
                            <td class="px-4 py-2 text-slate-600 whitespace-nowrap">
                                {{ $r->submission_time ? \Carbon\Carbon::parse($r->submission_time)->format('M j, Y g:i A') : '—' }}
                            </td>
                            <td class="px-4 py-2 text-slate-700 max-w-[260px] truncate" title="{{ $r->item_name }}">{{ $r->item_name ?: '—' }}</td>
                            <td class="px-4 py-2 text-right text-slate-700 whitespace-nowrap">{{ $r->cod === '' || $r->cod === null ? '—' : $r->cod }}</td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $badge }}">{{ $r->status }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center">
                                <p class="text-slate-400">No shipment rows match these filters.</p>
                                @if ($this->activeFilters)
                                    <button wire:click="clearFilters" class="mt-2 text-sm font-semibold text-indigo-600 hover:underline">Clear filters</button>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($rows->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $rows->onEachSide(1)->links() }}</div>
        @endif
    </div>
</div>
