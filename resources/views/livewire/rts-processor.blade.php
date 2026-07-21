<div>
    <!-- Header -->
    <div class="bg-slate-50 border-b border-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <h1 class="text-2xl font-bold text-gray-900">📦 RTS Processor</h1>
            <p class="text-sm text-gray-500">Upload your J&amp;T export to update shipment statuses, then monitor your RTS rates.</p>

            <div class="mt-4 flex gap-1 border-b border-slate-200">
                <button wire:click="$set('tab','upload')"
                        class="px-4 py-2 text-sm font-semibold -mb-px border-b-2 {{ $tab === 'upload' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                    ⬆️ Upload &amp; Update
                </button>
                <button wire:click="$set('tab','monitoring')"
                        class="px-4 py-2 text-sm font-semibold -mb-px border-b-2 {{ $tab === 'monitoring' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                    📊 RTS Monitoring
                </button>
            </div>
        </div>
    </div>

    <div class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">

        {{-- ============================ UPLOAD TAB ============================ --}}
        @if ($tab === 'upload')
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
                                <p class="text-xs text-gray-400">Upload #{{ $current->id }}</p>
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
                            <div class="flex items-center gap-2 text-sm text-gray-500">
                                <svg class="animate-spin w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                                {{ $current->status === 'processing' ? 'Updating statuses…' : 'Scanning file…' }} You can keep using the app.
                            </div>
                        @elseif ($current->status === 'done')
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
                                <div class="rounded-lg bg-slate-50 border border-slate-200 px-3 py-2"><div class="text-lg font-bold text-gray-900">{{ number_format($current->total_rows) }}</div><div class="text-xs text-gray-500">Total rows</div></div>
                                <div class="rounded-lg bg-green-50 border border-green-200 px-3 py-2"><div class="text-lg font-bold text-green-700">{{ number_format($current->inserted) }}</div><div class="text-xs text-gray-500">Inserted</div></div>
                                <div class="rounded-lg bg-blue-50 border border-blue-200 px-3 py-2"><div class="text-lg font-bold text-blue-700">{{ number_format($current->updated) }}</div><div class="text-xs text-gray-500">Updated</div></div>
                                <div class="rounded-lg bg-gray-50 border border-gray-200 px-3 py-2"><div class="text-lg font-bold text-gray-600">{{ number_format($current->skipped) }}</div><div class="text-xs text-gray-500">Skipped (final)</div></div>
                            </div>
                            <button wire:click="dismissCurrent" class="mt-3 text-xs font-semibold text-indigo-600 hover:text-indigo-800">Dismiss</button>
                        @elseif ($current->status === 'failed')
                            <p class="text-sm text-red-600">❌ {{ $current->error_message ?: 'Processing failed.' }}</p>
                            <button wire:click="dismissCurrent" class="mt-3 text-xs font-semibold text-indigo-600 hover:text-indigo-800">Dismiss</button>
                        @endif
                    </div>
                @endif

                {{-- Upload history --}}
                <div>
                    <h2 class="text-sm font-semibold text-gray-800 mb-2">Recent uploads</h2>
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <div class="overflow-x-auto" style="max-height:340px;overflow-y:auto;">
                            <table class="min-w-full text-xs">
                                <thead class="bg-gray-50 text-gray-600 sticky top-0">
                                    <tr>
                                        <th class="text-left px-3 py-2 font-medium">Uploaded</th>
                                        <th class="text-left px-3 py-2 font-medium">File</th>
                                        <th class="text-left px-3 py-2 font-medium">Status</th>
                                        <th class="text-right px-3 py-2 font-medium">Inserted</th>
                                        <th class="text-right px-3 py-2 font-medium">Updated</th>
                                        <th class="text-right px-3 py-2 font-medium">Skipped</th>
                                        <th class="text-right px-3 py-2 font-medium">Total</th>
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
                                            <td class="px-3 py-1.5 whitespace-nowrap text-gray-700">{{ $h->created_at?->timezone('Asia/Manila')->format('M j, g:i A') }}</td>
                                            <td class="px-3 py-1.5 text-gray-900 max-w-[240px] truncate" title="{{ $h->original_name }}">{{ $h->original_name }}</td>
                                            <td class="px-3 py-1.5"><span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold uppercase {{ $hb }}">{{ str_replace('_',' ',$h->status) }}</span></td>
                                            <td class="px-3 py-1.5 text-right tabular-nums text-gray-900">{{ number_format($h->inserted) }}</td>
                                            <td class="px-3 py-1.5 text-right tabular-nums text-gray-900">{{ number_format($h->updated) }}</td>
                                            <td class="px-3 py-1.5 text-right tabular-nums text-gray-600">{{ number_format($h->skipped) }}</td>
                                            <td class="px-3 py-1.5 text-right tabular-nums text-gray-500">{{ $h->total_rows ? number_format($h->total_rows) : '—' }}</td>
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
        @endif

        {{-- ============================ MONITORING TAB ============================ --}}
        @if ($tab === 'monitoring')
            <div x-data="{ q: '' }">
                <div class="flex flex-wrap items-end gap-3 bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-4">
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">From</label>
                        <input type="date" wire:model="from" class="border border-gray-300 rounded-lg p-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">To</label>
                        <input type="date" wire:model="to" class="border border-gray-300 rounded-lg p-2 text-sm">
                    </div>
                    <button wire:click="$refresh" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Apply</button>
                    <div class="flex-1"></div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">Search</label>
                        <input type="text" x-model="q" placeholder="Search sender / item…" class="border border-gray-300 rounded-lg p-2 text-sm min-w-[220px]">
                    </div>
                </div>

                @if (count($results))
                    <div class="border border-gray-200 rounded-xl shadow-sm overflow-hidden bg-white">
                        <div class="overflow-auto" style="max-height:calc(100vh - 320px);">
                            <table class="min-w-full text-xs">
                                <thead class="bg-slate-100 text-gray-600 sticky top-0">
                                    <tr>
                                        <th class="text-left px-3 py-2 font-semibold uppercase tracking-wide whitespace-nowrap">Date Range</th>
                                        <th class="text-left px-3 py-2 font-semibold uppercase tracking-wide whitespace-nowrap">Sender</th>
                                        <th class="text-left px-3 py-2 font-semibold uppercase tracking-wide whitespace-nowrap">Item</th>
                                        <th class="text-left px-3 py-2 font-semibold uppercase tracking-wide whitespace-nowrap">COD</th>
                                        <th class="text-right px-3 py-2 font-semibold uppercase tracking-wide">Qty</th>
                                        <th class="text-right px-3 py-2 font-semibold uppercase tracking-wide">RTS</th>
                                        <th class="text-right px-3 py-2 font-semibold uppercase tracking-wide">Del</th>
                                        <th class="text-right px-3 py-2 font-semibold uppercase tracking-wide">Transit</th>
                                        <th class="text-right px-3 py-2 font-semibold uppercase tracking-wide">RTS%</th>
                                        <th class="text-right px-3 py-2 font-semibold uppercase tracking-wide">Del%</th>
                                        <th class="text-right px-3 py-2 font-semibold uppercase tracking-wide">Transit%</th>
                                        <th class="text-right px-3 py-2 font-semibold uppercase tracking-wide">Cur RTS%</th>
                                        <th class="text-right px-3 py-2 font-semibold uppercase tracking-wide">Max RTS%</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($results as $r)
                                        @php
                                            $rtsColor = $r['rts_percent'] > 25 ? 'bg-red-100'
                                                      : ($r['rts_percent'] > 20 ? 'bg-orange-100'
                                                      : ($r['rts_percent'] > 15 ? 'bg-green-100' : 'bg-cyan-50'));
                                        @endphp
                                        <tr class="hover:bg-blue-50" x-show="q === '' || (@js(mb_strtolower($r['sender'].' '.$r['item'].' '.$r['cod']))).includes(q.toLowerCase())">
                                            <td class="px-3 py-1.5 whitespace-nowrap text-gray-600">{{ $r['date_range'] }}</td>
                                            <td class="px-3 py-1.5 whitespace-nowrap font-medium text-gray-800">{{ $r['sender'] }}</td>
                                            <td class="px-3 py-1.5 whitespace-nowrap text-gray-700">{{ $r['item'] }}</td>
                                            <td class="px-3 py-1.5 whitespace-nowrap text-gray-700">{{ $r['cod'] }}</td>
                                            <td class="px-3 py-1.5 text-right text-gray-700">{{ number_format($r['quantity']) }}</td>
                                            <td class="px-3 py-1.5 text-right" style="color:#b91c1c;">{{ number_format($r['rts_count']) }}</td>
                                            <td class="px-3 py-1.5 text-right" style="color:#15803d;">{{ number_format($r['delivered_count']) }}</td>
                                            <td class="px-3 py-1.5 text-right" style="color:#1d4ed8;">{{ number_format($r['transit_count']) }}</td>
                                            <td class="px-3 py-1.5 text-right font-semibold {{ $rtsColor }}">{{ number_format($r['rts_percent'], 2) }}%</td>
                                            <td class="px-3 py-1.5 text-right text-gray-700">{{ number_format($r['delivered_percent'], 2) }}%</td>
                                            <td class="px-3 py-1.5 text-right text-gray-700">{{ number_format($r['transit_percent'], 2) }}%</td>
                                            <td class="px-3 py-1.5 text-right text-gray-700">{{ is_numeric($r['current_rts']) ? number_format($r['current_rts'], 2).'%' : 'N/A' }}</td>
                                            <td class="px-3 py-1.5 text-right text-gray-700">{{ is_numeric($r['max_rts']) ? number_format($r['max_rts'], 2).'%' : 'N/A' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">{{ count($results) }} groups · sorted by RTS% (highest first)</p>
                @else
                    <div class="rounded-xl border-2 border-dashed border-gray-200 text-gray-400 text-center p-12">
                        No data in this date range. Upload a J&amp;T file first, or widen the range.
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
