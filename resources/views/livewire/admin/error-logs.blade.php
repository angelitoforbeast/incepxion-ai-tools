<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-xl font-bold text-slate-900 mb-1">Admin</h1>
    @include('partials.admin-nav')

    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Error Logs</h2>
            <p class="text-sm text-slate-500">Application errors captured across the app (like the Laravel log). Admin-only.</p>
        </div>
        @if ($total > 0)
            <button wire:click="clearLogs" wire:confirm="Clear all error logs? This can't be undone."
                    class="rounded-lg border border-rose-200 bg-white px-3 py-2 text-sm font-semibold text-rose-600 hover:bg-rose-50">
                Clear logs ({{ number_format($total) }})
            </button>
        @endif
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-400">
                        <th class="px-4 py-3 font-semibold">When</th>
                        <th class="px-4 py-3 font-semibold">Error</th>
                        <th class="px-4 py-3 font-semibold">Where</th>
                        <th class="px-4 py-3 font-semibold">User</th>
                        <th class="px-4 py-3 font-semibold text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rows as $log)
                        <tr class="hover:bg-slate-50 align-top">
                            <td class="px-4 py-3 text-slate-500 whitespace-nowrap">{{ $log->created_at?->timezone('Asia/Manila')->format('M d, g:i A') }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-rose-700 break-words">{{ class_basename($log->exception) }}</div>
                                <div class="text-xs text-slate-600 break-words">{{ \Illuminate\Support\Str::limit($log->message, 160) }}</div>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500 break-words max-w-[260px]">
                                @if ($log->url)<div class="truncate" title="{{ $log->url }}">{{ $log->method }} {{ $log->url }}</div>@endif
                                <div class="text-slate-400">{{ class_basename($log->file) }}:{{ $log->line }}</div>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ $log->user?->email ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <button wire:click="toggle({{ $log->id }})" class="text-xs font-semibold text-indigo-600 hover:underline">
                                    {{ $expandedId === $log->id ? 'Hide' : 'Trace' }}
                                </button>
                            </td>
                        </tr>
                        @if ($expandedId === $log->id)
                            <tr class="bg-slate-900">
                                <td colspan="5" class="px-4 py-3">
                                    <pre class="text-[11px] leading-relaxed text-slate-200 whitespace-pre-wrap break-words max-h-96 overflow-y-auto">{{ $log->message }}

{{ $log->trace }}</pre>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">No errors logged. 🎉</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($rows->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $rows->links() }}</div>
        @endif
    </div>
</div>
