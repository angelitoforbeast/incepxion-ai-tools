<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-xl font-bold text-slate-900 mb-1">Admin</h1>
    @include('partials.admin-nav')

    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Subscriptions</h2>
            <p class="text-sm text-slate-500">Set and extend each user's access validity. Payments are handled offline — extend here once a user has paid.</p>
        </div>
        <div>
            <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wide mb-1">Search</label>
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="Name or email…"
                   class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 min-w-[240px]">
        </div>
    </div>

    @if (session('msg'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">{{ session('msg') }}</div>
    @endif

    <!-- Users -->
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-400">
                        <th class="px-4 py-3 font-semibold">User</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 font-semibold">Expires</th>
                        <th class="px-4 py-3 font-semibold text-right">Manage</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $u)
                        @php
                            $exp = $u->access_expires_at;
                            if (! $exp) {
                                $b = 'bg-slate-100 text-slate-500'; $label = 'No expiry';
                            } elseif ($u->isExpired()) {
                                $b = 'bg-rose-100 text-rose-700'; $label = 'Expired';
                            } elseif ($u->isExpiringSoon()) {
                                $b = 'bg-amber-100 text-amber-700'; $label = 'Expiring soon';
                            } else {
                                $b = 'bg-emerald-100 text-emerald-700'; $label = 'Active';
                            }
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800">{{ $u->name }}</div>
                                <div class="text-xs text-slate-400">{{ $u->email }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $b }}">{{ $label }}</span>
                            </td>
                            <td class="px-4 py-3 {{ $exp && $u->isExpired() ? 'text-rose-600' : 'text-slate-600' }}">
                                {{ $exp ? $exp->format('M d, Y') : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button wire:click="manage({{ $u->id }})"
                                        class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">Manage</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-10 text-center text-slate-400">No approved users found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $users->links() }}</div>
        @endif
    </div>

    <!-- Change log link -->
    <div class="mt-4">
        <a href="{{ route('admin.subscriptions.log') }}" wire:navigate
           class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 hover:text-indigo-700">
            <svg style="width:16px;height:16px" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            View subscription change log →
        </a>
    </div>

    <!-- Manage modal -->
    @if ($managingUser)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/50" wire:click="closeManage"></div>
            <div class="relative w-full max-w-md rounded-2xl bg-white shadow-xl">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h3 class="text-base font-semibold text-slate-900">Manage subscription</h3>
                    <p class="text-sm text-slate-500">{{ $managingUser->name }} — {{ $managingUser->email }}</p>
                </div>

                <div class="p-6 space-y-5">
                    <div class="rounded-lg bg-slate-50 border border-slate-200 px-4 py-3 text-sm">
                        <span class="text-slate-500">Current expiry:</span>
                        <span class="font-semibold text-slate-800">
                            {{ $managingUser->access_expires_at ? $managingUser->access_expires_at->format('M d, Y') : 'None set' }}
                        </span>
                        @if ($managingUser->access_expires_at)
                            <span class="text-xs {{ $managingUser->isExpired() ? 'text-rose-600' : 'text-slate-400' }}">
                                ({{ $managingUser->access_expires_at->diffForHumans() }})
                            </span>
                        @endif
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Note <span class="font-normal normal-case text-slate-400">(optional — e.g. “Paid ₱500 via GCash”)</span></label>
                        <input type="text" wire:model="note" placeholder="Payment reference / remarks"
                               class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Quick extend</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach ([1, 3, 6, 12] as $m)
                                <button wire:click="extend({{ $m }})"
                                        class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-200">
                                    +{{ $m }} month{{ $m > 1 ? 's' : '' }}
                                </button>
                            @endforeach
                        </div>
                        <p class="mt-1 text-xs text-slate-400">Adds to the current expiry (or from today if already lapsed).</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Or set an exact date</label>
                        <div class="flex items-center gap-2">
                            <input type="date" wire:model="newDate"
                                   class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <button wire:click="setDate"
                                    class="rounded-lg bg-slate-800 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-900">Set date</button>
                        </div>
                        <p class="mt-1 text-xs text-slate-400">A past date immediately expires the account (useful for testing the settle page).</p>
                        @error('newDate') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="border-t border-slate-100 px-6 py-3 text-right">
                    <button wire:click="closeManage" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Close</button>
                </div>
            </div>
        </div>
    @endif
</div>
