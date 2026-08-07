<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-xl font-bold text-slate-900 mb-1">Admin</h1>
    @include('partials.admin-nav')

    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Profit Calculator — History</h2>
            <p class="text-sm text-slate-500">Every net-profit calculation, per user. Visible to admins only.</p>
        </div>
        <div>
            <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wide mb-1">Filter by user</label>
            <select wire:model.live="userId" class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 min-w-[220px]">
                <option value="">All users</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}">{{ $u->name }} — {{ $u->email }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-3 py-2 font-medium whitespace-nowrap">When</th>
                        <th class="text-left px-3 py-2 font-medium whitespace-nowrap">User</th>
                        <th class="text-right px-3 py-2 font-medium">CPP</th>
                        <th class="text-right px-3 py-2 font-medium">COGS</th>
                        <th class="text-right px-3 py-2 font-medium">Ship</th>
                        <th class="text-right px-3 py-2 font-medium">Orders</th>
                        <th class="text-right px-3 py-2 font-medium">COD</th>
                        <th class="text-right px-3 py-2 font-medium">COD Fee</th>
                        <th class="text-right px-3 py-2 font-medium">RTS</th>
                        <th class="text-right px-3 py-2 font-medium">Net Profit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rows as $r)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-1.5 whitespace-nowrap text-slate-600">{{ $r->created_at?->timezone('Asia/Manila')->format('M j, g:i A') }}</td>
                            <td class="px-3 py-1.5 text-slate-800 max-w-[200px] truncate" title="{{ $r->user?->email }}">{{ $r->user?->email ?? '—' }}</td>
                            <td class="px-3 py-1.5 text-right tabular-nums">{{ number_format($r->cpp, 2) }}</td>
                            <td class="px-3 py-1.5 text-right tabular-nums">{{ number_format($r->cogs, 2) }}</td>
                            <td class="px-3 py-1.5 text-right tabular-nums">{{ number_format($r->shipping_fee, 2) }}</td>
                            <td class="px-3 py-1.5 text-right tabular-nums">{{ number_format($r->orders) }}</td>
                            <td class="px-3 py-1.5 text-right tabular-nums">{{ number_format($r->cod_price, 2) }}</td>
                            <td class="px-3 py-1.5 text-right tabular-nums">{{ rtrim(rtrim(number_format($r->cod_fee, 4), '0'), '.') }}</td>
                            <td class="px-3 py-1.5 text-right tabular-nums">{{ rtrim(rtrim(number_format($r->rts, 4), '0'), '.') }}</td>
                            <td class="px-3 py-1.5 text-right tabular-nums font-semibold {{ $r->net_profit < 0 ? 'text-red-600' : 'text-indigo-700' }}">₱{{ number_format($r->net_profit, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="px-3 py-8 text-center text-slate-400">No calculations yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $rows->links() }}</div>
</div>
