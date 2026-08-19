<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-xl font-bold text-slate-900 mb-1">Admin</h1>
    @include('partials.admin-nav')

    <div class="mb-4 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Storage</h2>
            <p class="text-sm text-slate-500">Disk usage on the host server. Refreshes on load.</p>
        </div>
        <button wire:click="$refresh" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">↻ Refresh</button>
    </div>

    {{-- Disk cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-5">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Total</div>
            <div class="mt-1 text-2xl font-bold text-slate-900">{{ $totalH }}</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-5">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Used</div>
            <div class="mt-1 text-2xl font-bold {{ $percent >= 90 ? 'text-rose-600' : ($percent >= 75 ? 'text-amber-600' : 'text-slate-900') }}">{{ $usedH }}</div>
            <div class="text-xs text-slate-400">{{ $percent }}% of total</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-5">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Available</div>
            <div class="mt-1 text-2xl font-bold text-emerald-600">{{ $freeH }}</div>
        </div>
    </div>

    {{-- Usage bar --}}
    <div class="mt-4 rounded-2xl border border-slate-200 bg-white shadow-sm p-5">
        <div class="flex items-center justify-between text-sm text-slate-600 mb-2">
            <span>Disk usage</span>
            <span class="font-semibold">{{ $usedH }} / {{ $totalH }} ({{ $percent }}%)</span>
        </div>
        <div class="h-3 w-full rounded-full bg-slate-100 overflow-hidden">
            <div class="h-3 rounded-full {{ $percent >= 90 ? 'bg-rose-500' : ($percent >= 75 ? 'bg-amber-500' : 'bg-indigo-500') }}"
                 style="width: {{ max(1, $percent) }}%"></div>
        </div>
        @if ($percent >= 90)
            <p class="mt-2 text-xs text-rose-600 font-semibold">⚠️ Disk is nearly full — free up space or upgrade the server.</p>
        @endif
    </div>

    {{-- Breakdown --}}
    <div class="mt-4 rounded-2xl border border-slate-200 bg-white shadow-sm p-5">
        <h3 class="text-sm font-semibold text-slate-900 mb-3">What's using space (app)</h3>
        <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
            <div>
                <dt class="text-slate-400">Uploads folder (storage/app)</dt>
                <dd class="font-semibold text-slate-800">{{ $storageAppH }}</dd>
            </div>
            <div>
                <dt class="text-slate-400">Database size</dt>
                <dd class="font-semibold text-slate-800">{{ $dbMb !== null ? number_format($dbMb, 1).' MB' : '—' }}</dd>
            </div>
            <div>
                <dt class="text-slate-400">RTS records (from_jnts)</dt>
                <dd class="font-semibold text-slate-800">{{ number_format($fromJnts) }} rows</dd>
            </div>
        </dl>
        <p class="mt-3 text-xs text-slate-400">Tip: prune old RTS data in <a href="{{ route('admin.rts') }}" wire:navigate class="text-indigo-600 underline">RTS Data</a> if the database grows too large.</p>
    </div>
</div>
