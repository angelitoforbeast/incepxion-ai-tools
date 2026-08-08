<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-xl font-bold text-slate-900 mb-1">Admin</h1>
    @include('partials.admin-nav')

    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Access Log</h2>
            <p class="text-sm text-slate-500">Logins and device-conflict sign-outs — with IP, device, and location. Admin-only.</p>
        </div>
        <div class="flex items-end gap-3">
            <div>
                <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wide mb-1">User</label>
                <select wire:model.live="userId" class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 min-w-[200px]">
                    <option value="">All users</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} — {{ $u->email }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wide mb-1">Event</label>
                <select wire:model.live="type" class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All events</option>
                    <option value="login">Login</option>
                    <option value="device_signout">Device sign-out</option>
                </select>
            </div>
            @if ($activeFilters)
                <button wire:click="clearFilters" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Clear ({{ $activeFilters }})</button>
            @endif
        </div>
    </div>

    @php
        $sortArrow = fn ($col) => $sortBy === $col ? ($sortDir === 'asc' ? '▲' : '▼') : '↕';
        $sortCls = fn ($col) => $sortBy === $col ? 'text-indigo-600' : 'text-slate-300';
    @endphp

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-3 py-2 font-medium whitespace-nowrap">
                            <button wire:click="sort('created_at')" class="inline-flex items-center gap-1 hover:text-indigo-600">When <span class="{{ $sortCls('created_at') }}">{{ $sortArrow('created_at') }}</span></button>
                        </th>
                        <th class="text-left px-3 py-2 font-medium whitespace-nowrap">
                            <button wire:click="sort('user_id')" class="inline-flex items-center gap-1 hover:text-indigo-600">User <span class="{{ $sortCls('user_id') }}">{{ $sortArrow('user_id') }}</span></button>
                        </th>
                        <th class="text-left px-3 py-2 font-medium whitespace-nowrap">
                            <button wire:click="sort('type')" class="inline-flex items-center gap-1 hover:text-indigo-600">Event <span class="{{ $sortCls('type') }}">{{ $sortArrow('type') }}</span></button>
                        </th>
                        <th class="text-left px-3 py-2 font-medium whitespace-nowrap">
                            <button wire:click="sort('ip_address')" class="inline-flex items-center gap-1 hover:text-indigo-600">IP <span class="{{ $sortCls('ip_address') }}">{{ $sortArrow('ip_address') }}</span></button>
                        </th>
                        <th class="text-left px-3 py-2 font-medium whitespace-nowrap">Device</th>
                        <th class="text-left px-3 py-2 font-medium whitespace-nowrap">Location</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rows as $r)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-1.5 whitespace-nowrap text-slate-600">{{ $r->created_at?->timezone('Asia/Manila')->format('M j, Y g:i A') }}</td>
                            <td class="px-3 py-1.5 text-slate-800 max-w-[200px] truncate" title="{{ $r->user?->email }}">{{ $r->user?->email ?? '—' }}</td>
                            <td class="px-3 py-1.5">
                                <span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold uppercase {{ $r->type === 'device_signout' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">{{ str_replace('_', ' ', $r->type) }}</span>
                            </td>
                            <td class="px-3 py-1.5 whitespace-nowrap font-mono text-slate-700">{{ $r->ip_address ?? '—' }}</td>
                            <td class="px-3 py-1.5 whitespace-nowrap text-slate-700" title="{{ $r->user_agent }}">{{ \App\Models\AccessLog::formatDevice($r->user_agent) }}</td>
                            <td class="px-3 py-1.5 whitespace-nowrap text-slate-700">{{ $r->location ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-8 text-center text-slate-400">No access events yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $rows->links() }}</div>
</div>
