<div>
    @include('partials.rts-nav')

    <div class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="max-w-2xl space-y-5">
            <form wire:submit="submitUpload" class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">J&amp;T export file <span class="text-gray-400 font-normal">(.zip, .csv, .xlsx)</span></label>
                    <input type="file" wire:model="file" accept=".zip,.csv,.xlsx"
                           class="block w-full text-sm border border-gray-300 rounded-lg p-2 file:mr-3 file:rounded file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-indigo-700 file:font-semibold" />
                    @error('file') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    <div wire:loading wire:target="file" class="text-xs text-gray-400 mt-1">Uploading file…</div>
                </div>

                <button type="submit" wire:loading.attr="disabled" wire:target="file,submitUpload"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
                    <span wire:loading.remove wire:target="submitUpload">⬆️ Upload &amp; Process</span>
                    <span wire:loading wire:target="submitUpload">Queuing…</span>
                </button>
            </form>

            {{-- Live status of the current upload --}}
            @if ($current)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5"
                     @if (in_array($current->status, ['queued','scanning','processing'])) wire:poll.2s="poll" @endif>
                    <div class="flex items-center justify-between mb-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-900 truncate">{{ $current->original_name }}</p>
                        </div>
                        @php
                            $badge = match ($current->status) {
                                'done'               => 'bg-green-100 text-green-800',
                                'processing','scanning' => 'bg-blue-100 text-blue-800',
                                'queued'             => 'bg-gray-100 text-gray-700',
                                'needs_confirmation' => 'bg-amber-100 text-amber-800',
                                'failed'             => 'bg-red-100 text-red-800',
                                'canceled'           => 'bg-gray-100 text-gray-500',
                                default              => 'bg-gray-100 text-gray-700',
                            };
                        @endphp
                        <span class="inline-block px-2.5 py-1 rounded text-[11px] font-semibold uppercase {{ $badge }}">
                            {{ str_replace('_', ' ', $current->status) }}
                        </span>
                    </div>

                    {{-- ⚠️ Invalid-transition prompt --}}
                    @if ($current->status === 'needs_confirmation')
                        <div class="rounded-lg border border-amber-300 bg-amber-50 p-4">
                            <p class="text-sm font-semibold text-amber-900">⚠️ Possible wrong file</p>
                            <p class="text-sm text-amber-800 mt-1">
                                Found <strong>{{ number_format($current->conflict_count) }}</strong> shipment(s) that would go
                                <strong>backward</strong> (e.g. already DELIVERED but this file says IN TRANSIT).
                                This usually means an <strong>older / wrong file</strong> was uploaded.
                            </p>

                            @if (! empty($current->conflicts))
                                <div class="mt-3 max-h-40 overflow-y-auto rounded border border-amber-200 bg-white">
                                    <table class="min-w-full text-xs">
                                        <thead class="bg-amber-100/60 text-amber-900">
                                            <tr>
                                                <th class="text-left px-3 py-1.5 font-semibold">Waybill</th>
                                                <th class="text-left px-3 py-1.5 font-semibold">Current</th>
                                                <th class="text-left px-3 py-1.5 font-semibold">In file</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-amber-100">
                                            @foreach ($current->conflicts as $c)
                                                <tr>
                                                    <td class="px-3 py-1 font-mono text-gray-700">{{ $c['waybill'] ?? '' }}</td>
                                                    <td class="px-3 py-1 text-green-700">{{ $c['from'] ?? '' }}</td>
                                                    <td class="px-3 py-1 text-red-700">{{ $c['to'] ?? '' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @if ($current->conflict_count > count($current->conflicts))
                                    <p class="text-[11px] text-amber-700 mt-1">…and {{ number_format($current->conflict_count - count($current->conflicts)) }} more.</p>
                                @endif
                            @endif

                            <div class="mt-4 flex items-center gap-2">
                                <button wire:click="confirmUpload" wire:confirm="Continue anyway? The backward rows will be skipped (their final status is kept)."
                                        class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">
                                    Continue (skip backward rows)
                                </button>
                                <button wire:click="cancelUpload" wire:confirm="Cancel this upload and discard the file?"
                                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50">
                                    Cancel upload
                                </button>
                            </div>
                        </div>
                    @elseif (in_array($current->status, ['queued','scanning','processing']))
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                    <svg class="animate-spin w-4 h-4 text-indigo-500 flex-shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                                    @if ($current->status === 'processing')
                                        <span>Saving to database…</span>
                                    @elseif ($current->scanned_rows > 0)
                                        <span>Scanning… <strong>{{ number_format($current->scanned_rows) }}</strong> rows read</span>
                                    @else
                                        <span>Scanning file…</span>
                                    @endif
                                    {{-- Elapsed timer, anchored to the job's real start time so it never
                                         resets when you switch tabs or navigate away and back. --}}
                                    @php
                                        $startedEpoch = $current->started_at
                                            ? \Illuminate\Support\Carbon::parse($current->started_at->format('Y-m-d H:i:s'), 'Asia/Manila')->timestamp
                                            : null;
                                    @endphp
                                    @if ($startedEpoch)
                                        <span class="text-gray-400" wire:key="rts-timer-{{ $current->id }}"
                                              x-data="{ s: 0, start: {{ $startedEpoch }} }"
                                              x-init="const tick = () => s = Math.max(0, Math.floor(Date.now()/1000 - start)); tick(); setInterval(tick, 1000);"
                                              x-text="'· ' + Math.floor(s/60) + 'm ' + String(s%60).padStart(2,'0') + 's'"></span>
                                    @endif
                                </div>
                                {{-- Plain form submit (not Livewire) so Cancel works even if the tab's Livewire runtime is stale. --}}
                                <form method="POST" action="{{ route('tools.rts.cancel') }}" class="flex-shrink-0"
                                      onsubmit="return confirm('Stop this upload? Any progress will be discarded and no data imported.');">
                                    @csrf
                                    <input type="hidden" name="upload" value="{{ $current->id }}">
                                    <button type="submit"
                                            class="rounded-md border border-rose-200 bg-white px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50">
                                        Cancel
                                    </button>
                                </form>
                            </div>
                            <p class="text-xs text-gray-400">You can keep using the app — this runs in the background.</p>
                        </div>
                    @elseif ($current->status === 'canceled')
                        <p class="text-sm text-gray-500">🛑 Upload canceled — no data was imported from this file.</p>
                        <button wire:click="dismissCurrent" class="mt-3 text-xs font-semibold text-indigo-600 hover:text-indigo-800">Dismiss</button>
                    @elseif ($current->status === 'done')
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
                            <div class="rounded-lg bg-slate-50 border border-slate-200 px-3 py-2"><div class="text-lg font-bold text-gray-900">{{ number_format($current->total_rows) }}</div><div class="text-xs text-gray-500">Total rows</div></div>
                            <div class="rounded-lg bg-green-50 border border-green-200 px-3 py-2"><div class="text-lg font-bold text-green-700">{{ number_format($current->inserted) }}</div><div class="text-xs text-gray-500">Inserted</div></div>
                            <div class="rounded-lg bg-blue-50 border border-blue-200 px-3 py-2"><div class="text-lg font-bold text-blue-700">{{ number_format($current->updated) }}</div><div class="text-xs text-gray-500">Updated</div></div>
                            <div class="rounded-lg bg-gray-50 border border-gray-200 px-3 py-2"><div class="text-lg font-bold text-gray-600">{{ number_format($current->skipped) }}</div><div class="text-xs text-gray-500">Skipped (final)</div></div>
                        </div>
                        @if ($current->user_message)
                            <p class="mt-3 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">⚠️ {{ $current->user_message }}</p>
                        @endif
                        <a href="{{ route('tools.rts.monitor') }}" wire:navigate class="mt-3 inline-block text-xs font-semibold text-indigo-600 hover:text-indigo-800">View RTS Monitoring →</a>
                        <button wire:click="dismissCurrent" class="mt-3 ml-3 text-xs font-semibold text-gray-400 hover:text-gray-600">Dismiss</button>
                    @elseif ($current->status === 'failed')
                        <p class="text-sm text-red-600">❌ {{ $current->user_message ?: 'This file couldn’t be processed. Please try again.' }}</p>
                        <button wire:click="dismissCurrent" class="mt-3 text-xs font-semibold text-indigo-600 hover:text-indigo-800">Dismiss</button>
                    @endif
                </div>
            @endif

            {{-- Upload history --}}
            <div>
                <h2 class="text-sm font-semibold text-gray-800 mb-2">Recent uploads</h2>
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <div style="max-height:340px;overflow-y:auto;">
                        <table class="w-full text-xs table-fixed">
                            <colgroup>
                                <col style="width:20%"><col style="width:26%"><col style="width:14%">
                                <col style="width:10%"><col style="width:10%"><col style="width:10%"><col style="width:10%">
                            </colgroup>
                            <thead class="bg-gray-50 text-gray-600 sticky top-0">
                                <tr>
                                    <th class="text-left px-2 py-2 font-medium">Uploaded</th>
                                    <th class="text-left px-2 py-2 font-medium">File</th>
                                    <th class="text-left px-2 py-2 font-medium">Status</th>
                                    <th class="text-right px-2 py-2 font-medium">Ins</th>
                                    <th class="text-right px-2 py-2 font-medium">Upd</th>
                                    <th class="text-right px-2 py-2 font-medium">Skip</th>
                                    <th class="text-right px-2 py-2 font-medium">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse ($history as $h)
                                    @php
                                        $hb = match ($h->status) {
                                            'done'               => 'bg-green-100 text-green-800',
                                            'processing','scanning' => 'bg-blue-100 text-blue-800',
                                            'queued'             => 'bg-gray-100 text-gray-700',
                                            'needs_confirmation' => 'bg-amber-100 text-amber-800',
                                            'failed'             => 'bg-red-100 text-red-800',
                                            'canceled'           => 'bg-gray-100 text-gray-500',
                                            default              => 'bg-gray-100 text-gray-700',
                                        };
                                    @endphp
                                    <tr class="hover:bg-gray-50 cursor-pointer" wire:click="$set('currentUploadId', {{ $h->id }})">
                                        <td class="px-2 py-1.5 truncate text-gray-700" title="{{ $h->created_at?->timezone('Asia/Manila')->format('M j, Y g:i A') }}">{{ $h->created_at?->timezone('Asia/Manila')->format('M j, g:i A') }}</td>
                                        <td class="px-2 py-1.5 text-gray-900 truncate" title="{{ $h->original_name }}">{{ $h->original_name }}</td>
                                        <td class="px-2 py-1.5"><span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase {{ $hb }}">{{ str_replace('_',' ',$h->status) }}</span></td>
                                        <td class="px-2 py-1.5 text-right tabular-nums text-gray-900">{{ number_format($h->inserted) }}</td>
                                        <td class="px-2 py-1.5 text-right tabular-nums text-gray-900">{{ number_format($h->updated) }}</td>
                                        <td class="px-2 py-1.5 text-right tabular-nums text-gray-600">{{ number_format($h->skipped) }}</td>
                                        <td class="px-2 py-1.5 text-right tabular-nums text-gray-500">{{ $h->total_rows ? number_format($h->total_rows) : '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="px-3 py-6 text-center text-gray-400">No uploads yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
