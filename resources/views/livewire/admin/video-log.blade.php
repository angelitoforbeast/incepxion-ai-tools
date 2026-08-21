<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-xl font-bold text-slate-900 mb-1">Admin</h1>
    @include('partials.admin-nav')

    <div class="mb-4">
        <h2 class="text-lg font-semibold text-slate-900">Video Log</h2>
        <p class="text-sm text-slate-500">Who opened which course video, when, and from where. Recorded server-side — a viewer cannot switch it off.</p>
    </div>

    {{-- Accounts watching from several places at once --}}
    @if ($this->sharing->isNotEmpty())
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 mb-4">
            <h3 class="text-sm font-semibold text-amber-900">⚠️ Possible shared accounts <span class="font-normal text-amber-700">— last 24 hours</span></h3>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($this->sharing as $s)
                    <button wire:click="focusUser({{ $s->user_id }})"
                            class="rounded-lg border border-amber-300 bg-white px-3 py-2 text-left hover:bg-amber-100">
                        <span class="block text-sm font-semibold text-slate-800">{{ $s->user?->name ?? 'User #'.$s->user_id }}</span>
                        <span class="block text-xs text-amber-700">{{ $s->ips }} different IPs · {{ $s->views }} views</span>
                    </button>
                @endforeach
            </div>
            <p class="mt-3 text-xs text-amber-700">One person can legitimately switch between wifi and mobile data — treat this as a prompt to look, not proof.</p>
        </div>
    @endif

    {{-- Filters --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-4 mb-4">
        <div class="flex flex-wrap items-end gap-3">
            <div class="min-w-[190px]">
                <label class="block text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-1">User</label>
                <select wire:model.live="userId" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All users</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-[220px]">
                <label class="block text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-1">Lesson</label>
                <select wire:model.live="lessonId" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All lessons</option>
                    @foreach ($lessons as $l)
                        <option value="{{ $l->id }}">{{ \Illuminate\Support\Str::limit($l->title, 40) }}</option>
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

            <div class="w-44">
                <label class="block text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-1">IP starts with</label>
                <input type="text" wire:model.live.debounce.400ms="ip" placeholder="49.144."
                       class="w-full rounded-lg border-slate-300 font-mono text-sm focus:border-indigo-500 focus:ring-indigo-500">
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
                    No views recorded
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

        <div class="overflow-auto max-h-[65vh]" wire:loading.class="opacity-50">
            <table class="min-w-full text-sm">
                <thead class="sticky top-0 z-10">
                    <tr class="text-left text-[11px] uppercase tracking-wide text-slate-500">
                        @foreach (['When', 'User', 'Lesson', 'Device', 'IP', 'Code'] as $h)
                            <th class="bg-slate-100 border-b border-slate-200 px-4 py-2.5 font-semibold whitespace-nowrap">{{ $h }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rows as $r)
                        <tr class="hover:bg-indigo-50/40">
                            <td class="px-4 py-2 text-slate-500 whitespace-nowrap">{{ $r->created_at?->timezone('Asia/Manila')->format('M j, Y g:i A') }}</td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <span class="font-medium text-slate-800">{{ $r->user?->name ?? '—' }}</span>
                                <span class="block text-xs text-slate-400">{{ $r->user?->email }}</span>
                            </td>
                            <td class="px-4 py-2 text-slate-700 max-w-[280px] truncate" title="{{ $r->lesson?->title }}">{{ $r->lesson?->title ?? '—' }}</td>
                            <td class="px-4 py-2 text-slate-600 whitespace-nowrap">{{ $r->device }}</td>
                            <td class="px-4 py-2 font-mono text-xs text-slate-600 whitespace-nowrap">{{ $r->ip_address ?? '—' }}</td>
                            <td class="px-4 py-2 font-mono text-xs text-indigo-700 whitespace-nowrap">{{ $r->watermark_code ? 'WM-'.$r->watermark_code : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center">
                                <p class="text-slate-400">Nothing recorded for these filters.</p>
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
