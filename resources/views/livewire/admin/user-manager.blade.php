<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="mb-6">
        <h1 class="text-xl font-bold text-slate-900">Admin · User Management</h1>
        <p class="text-sm text-slate-500">Approve, reject, or manage users.</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        @foreach ([
            ['Total Users', $stats['total'], 'text-slate-900'],
            ['Pending', $stats['pending'], 'text-amber-600'],
            ['Approved', $stats['approved'], 'text-emerald-600'],
            ['Generations Today', $stats['gensToday'], 'text-indigo-600'],
        ] as [$label, $val, $color])
            <div class="rounded-xl bg-white border border-slate-200 p-4 shadow-sm">
                <p class="text-xs text-slate-400">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold {{ $color }}">{{ $val }}</p>
            </div>
        @endforeach
    </div>

    @if (session('msg'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-2.5 text-sm text-emerald-700">
            ✓ {{ session('msg') }}
        </div>
    @endif

    <!-- Controls -->
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div class="inline-flex rounded-lg border border-slate-200 bg-white p-1">
            @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'all' => 'All'] as $key => $label)
                <button wire:click="$set('filter', '{{ $key }}')"
                        class="px-3 py-1.5 text-sm font-medium rounded-md transition {{ $filter === $key ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search name/email..."
               class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 w-full sm:w-64">
    </div>

    <!-- Table -->
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left text-xs uppercase tracking-wide text-slate-400">
                    <th class="px-4 py-3 font-semibold">User</th>
                    <th class="px-4 py-3 font-semibold">Plan</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3 font-semibold">Joined</th>
                    <th class="px-4 py-3 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($users as $u)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                @if ($u->avatar)
                                    <img src="{{ $u->avatar }}" class="w-8 h-8 rounded-full object-cover" alt="">
                                @else
                                    <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-semibold">{{ strtoupper(substr($u->name, 0, 1)) }}</div>
                                @endif
                                <div>
                                    <div class="font-medium text-slate-800">{{ $u->name }}
                                        @if ($u->isAdmin())<span class="ml-1 text-[10px] bg-indigo-100 text-indigo-700 rounded px-1.5 py-0.5">admin</span>@endif
                                    </div>
                                    <div class="text-xs text-slate-400">{{ $u->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $u->plan?->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @php $badge = [
                                'pending' => 'bg-amber-100 text-amber-700',
                                'approved' => 'bg-emerald-100 text-emerald-700',
                                'rejected' => 'bg-rose-100 text-rose-700',
                                'suspended' => 'bg-slate-200 text-slate-600',
                            ][$u->status] ?? 'bg-slate-100 text-slate-600'; @endphp
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badge }}">{{ ucfirst($u->status) }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ $u->created_at->format('M d, Y') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                @if ($u->status === 'pending')
                                    <button wire:click="approve({{ $u->id }})" class="rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">Approve</button>
                                    <button wire:click="reject({{ $u->id }})" wire:confirm="Reject this user?" class="rounded-md bg-white border border-slate-200 px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50">Reject</button>
                                @elseif ($u->status === 'approved')
                                    @if ($u->isAdmin())
                                        @if ($u->id !== auth()->id())
                                            <button wire:click="removeAdmin({{ $u->id }})" wire:confirm="Remove admin role from this user?" class="rounded-md bg-white border border-slate-200 px-3 py-1.5 text-xs font-semibold text-amber-600 hover:bg-amber-50">Remove Admin</button>
                                        @endif
                                    @else
                                        <button wire:click="makeAdmin({{ $u->id }})" wire:confirm="Make this user an admin?" class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">Make Admin</button>
                                    @endif
                                    <button wire:click="suspend({{ $u->id }})" wire:confirm="Suspend this user?" class="rounded-md bg-white border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">Suspend</button>
                                @else
                                    <button wire:click="reinstate({{ $u->id }})" class="rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">Reinstate</button>
                                @endif

                                @if ($u->id !== auth()->id())
                                    <button wire:click="deleteUser({{ $u->id }})"
                                            wire:confirm="Permanently delete {{ $u->name }} and ALL their data? This cannot be undone."
                                            title="Delete account"
                                            class="rounded-md p-1.5 text-rose-500 hover:bg-rose-50">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">No users in this filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>
</div>
