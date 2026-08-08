<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-xl font-bold text-slate-900 mb-1">Admin</h1>
    @include('partials.admin-nav')

    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <div>
            <a href="{{ route('admin.subscriptions') }}" wire:navigate class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">← Back to Subscriptions</a>
            <h2 class="mt-1 text-lg font-semibold text-slate-900">Subscription change log</h2>
            <p class="text-sm text-slate-500">Every change to a user's access validity — extend, set, approve, reinstate, first login.</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="mb-4 flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wide mb-1">User</label>
            <select wire:model.live="userId" class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 min-w-[220px]">
                <option value="">All users</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}">{{ $u->name }} — {{ $u->email }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wide mb-1">Action</label>
            <select wire:model.live="action" class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All actions</option>
                <option value="extend">Extend</option>
                <option value="set">Set date</option>
                <option value="approve">Approve</option>
                <option value="reinstate">Reinstate</option>
                <option value="first_login">First login</option>
            </select>
        </div>
        <div>
            <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wide mb-1">From</label>
            <input type="date" wire:model.live="dateFrom" class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wide mb-1">To</label>
            <input type="date" wire:model.live="dateTo" class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        @if ($activeFilters)
            <button wire:click="clearFilters" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Clear ({{ $activeFilters }})</button>
        @endif
    </div>

    <!-- Table -->
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-400">
                        <th class="px-4 py-3 font-semibold">When</th>
                        <th class="px-4 py-3 font-semibold">User</th>
                        <th class="px-4 py-3 font-semibold">Action</th>
                        <th class="px-4 py-3 font-semibold">Change</th>
                        <th class="px-4 py-3 font-semibold">By</th>
                        <th class="px-4 py-3 font-semibold">Note</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rows as $log)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-slate-500 whitespace-nowrap">
                                {{ $log->created_at->timezone('Asia/Manila')->format('M d, Y g:i A') }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800">{{ $log->user?->name ?? '—' }}</div>
                                <div class="text-xs text-slate-400">{{ $log->user?->email }}</div>
                            </td>
                            <td class="px-4 py-3">
                                @php $actionBadge = [
                                    'extend'      => 'bg-indigo-100 text-indigo-700',
                                    'set'         => 'bg-sky-100 text-sky-700',
                                    'approve'     => 'bg-emerald-100 text-emerald-700',
                                    'reinstate'   => 'bg-emerald-100 text-emerald-700',
                                    'first_login' => 'bg-slate-100 text-slate-600',
                                ][$log->action] ?? 'bg-slate-100 text-slate-600'; @endphp
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $actionBadge }}">
                                    {{ $log->action === 'extend' && $log->months ? "+{$log->months}m" : str_replace('_', ' ', $log->action) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-600 whitespace-nowrap">
                                <span class="text-slate-400">{{ $log->old_expires_at ? $log->old_expires_at->format('M d, Y') : '—' }}</span>
                                <span class="mx-1 text-slate-400">→</span>
                                <span class="font-medium text-slate-800">{{ $log->new_expires_at ? $log->new_expires_at->format('M d, Y') : '—' }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $log->admin?->name ?? 'System' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $log->note ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">No changes match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($rows->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $rows->links() }}</div>
        @endif
    </div>
</div>
