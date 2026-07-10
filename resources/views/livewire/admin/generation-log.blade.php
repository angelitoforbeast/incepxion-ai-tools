<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <h1 class="text-xl font-bold text-slate-900 mb-1">Admin</h1>
    @include('partials.admin-nav')

    <div class="mb-6">
        <h2 class="text-lg font-semibold text-slate-900">Data Logs</h2>
        <p class="text-sm text-slate-500">Every generation — inputs, outputs, and what users copied.</p>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left text-xs uppercase tracking-wide text-slate-400">
                    <th class="px-4 py-3 font-semibold">User</th>
                    <th class="px-4 py-3 font-semibold">Product</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3 font-semibold">Copied</th>
                    <th class="px-4 py-3 font-semibold">Tokens</th>
                    <th class="px-4 py-3 font-semibold">When</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($logs as $log)
                    <tr class="hover:bg-slate-50 align-top">
                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-800">{{ $log->user?->name ?? '—' }}</div>
                            <div class="text-xs text-slate-400">{{ $log->user?->email }}</div>
                        </td>
                        <td class="px-4 py-3 text-slate-700">{{ data_get($log->input, 'product_name', '—') }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $log->status === 'success' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">{{ ucfirst($log->status) }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ is_array($log->copies) ? count($log->copies) : 0 }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $log->input_tokens + $log->output_tokens }}</td>
                        <td class="px-4 py-3 text-slate-500 whitespace-nowrap">{{ $log->created_at->diffForHumans() }}</td>
                        <td class="px-4 py-3 text-right">
                            <button wire:click="toggle({{ $log->id }})" class="text-xs font-semibold text-indigo-600 hover:underline">
                                {{ $expanded === $log->id ? 'Hide' : 'View' }}
                            </button>
                        </td>
                    </tr>
                    @if ($expanded === $log->id)
                        <tr class="bg-slate-50">
                            <td colspan="7" class="px-4 py-4">
                                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 text-xs">
                                    <div>
                                        <div class="font-semibold text-slate-600 mb-1">Input</div>
                                        <pre class="whitespace-pre-wrap break-words rounded-lg bg-white border border-slate-200 p-3 max-h-64 overflow-auto">{{ json_encode($log->input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-slate-600 mb-1">Output</div>
                                        <pre class="whitespace-pre-wrap break-words rounded-lg bg-white border border-slate-200 p-3 max-h-64 overflow-auto">{{ json_encode($log->output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-slate-600 mb-1">Copied</div>
                                        <pre class="whitespace-pre-wrap break-words rounded-lg bg-white border border-slate-200 p-3 max-h-64 overflow-auto">{{ $log->copies ? json_encode($log->copies, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : 'None' }}</pre>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">No generations yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $logs->links() }}</div>
</div>
