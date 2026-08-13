<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-xl font-bold text-slate-900 mb-1">Admin</h1>
    @include('partials.admin-nav')

    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">RTS Data</h2>
            <p class="text-sm text-slate-500">Permanently delete a user's imported J&amp;T records so they can start fresh. This cannot be undone.</p>
        </div>
        <div>
            <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wide mb-1">Search</label>
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="Name or email…"
                   class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 min-w-[240px]">
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-400">
                        <th class="px-4 py-3 font-semibold">User</th>
                        <th class="px-4 py-3 font-semibold text-right">Records</th>
                        <th class="px-4 py-3 font-semibold text-right">Uploads</th>
                        <th class="px-4 py-3 font-semibold">Last upload</th>
                        <th class="px-4 py-3 font-semibold text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rows as $u)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800">{{ $u->name }}</div>
                                <div class="text-xs text-slate-400">{{ $u->email }}</div>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-700">{{ number_format($u->from_jnts_count) }}</td>
                            <td class="px-4 py-3 text-right text-slate-600">{{ number_format($u->rts_uploads_count) }}</td>
                            <td class="px-4 py-3 text-slate-500">
                                {{ $u->rts_uploads_max_batch_at ? \Illuminate\Support\Carbon::parse($u->rts_uploads_max_batch_at)->format('M d, Y') : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button wire:click="confirmDelete({{ $u->id }})"
                                        class="rounded-md bg-white border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50">
                                    Delete data
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">No users have imported RTS data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($rows->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $rows->links() }}</div>
        @endif
    </div>

    <!-- Delete-confirmation modal -->
    @if ($deletingUser)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data="{ typed: @entangle('deleteConfirm') }">
            <div class="fixed inset-0 bg-slate-900/50" wire:click="cancelDelete"></div>
            <div class="relative w-full max-w-md rounded-2xl bg-white shadow-xl">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h3 class="text-base font-semibold text-rose-700">Delete all RTS data?</h3>
                    <p class="text-sm text-slate-500">{{ $deletingUser->name }} — {{ $deletingUser->email }}</p>
                </div>

                <div class="p-6 space-y-4">
                    <div class="rounded-lg bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-800">
                        This permanently deletes
                        <strong>{{ number_format($deletingUser->from_jnts_count) }}</strong> imported record{{ $deletingUser->from_jnts_count == 1 ? '' : 's' }}
                        and <strong>{{ number_format($deletingUser->rts_uploads_count) }}</strong> upload{{ $deletingUser->rts_uploads_count == 1 ? '' : 's' }}.
                        <span class="font-semibold">This cannot be undone.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Type <span class="font-mono bg-slate-100 px-1 rounded text-rose-600">DELETE</span> to confirm</label>
                        <input type="text" wire:model.live="deleteConfirm" autocomplete="off"
                               class="w-full rounded-lg border-slate-300 text-sm focus:border-rose-500 focus:ring-rose-500"
                               placeholder="DELETE">
                        @error('deleteConfirm') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="border-t border-slate-100 px-6 py-3 flex items-center justify-end gap-2">
                    <button wire:click="cancelDelete" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button wire:click="deleteData"
                            x-bind:disabled="typed.trim() !== 'DELETE'"
                            class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-40 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="deleteData">Delete everything</span>
                        <span wire:loading wire:target="deleteData">Deleting…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
