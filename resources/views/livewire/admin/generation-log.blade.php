<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <h1 class="text-xl font-bold text-slate-900 mb-1">Admin</h1>
    @include('partials.admin-nav')

    <div class="mb-4">
        <h2 class="text-lg font-semibold text-slate-900">Ad Copy Generation History</h2>
        <p class="text-sm text-slate-500">Every generation — inputs, outputs, and what users copied (highlighted in green).</p>
    </div>

    {{-- Filters --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-4 mb-4">
        <div class="flex flex-wrap items-end gap-3">
            <div class="min-w-[180px]">
                <label class="block text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-1">User</label>
                <select wire:model.live="userId" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All users</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-36">
                <label class="block text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-1">Status</label>
                <select wire:model.live="status" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All</option>
                    <option value="success">Success</option>
                    <option value="error">Error</option>
                </select>
            </div>

            <div class="w-40">
                <label class="block text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-1">Model</label>
                <select wire:model.live="model" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All models</option>
                    @foreach ($models as $m)
                        <option value="{{ $m }}">{{ $m }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-40">
                <label class="block text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-1">From</label>
                <x-date-field model="from" size="text-sm" class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
            </div>

            <div class="w-40">
                <label class="block text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-1">To</label>
                <x-date-field model="to" size="text-sm" class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
            </div>

            <div class="flex-1 min-w-[200px]">
                <label class="block text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-1">Product</label>
                <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search product name…"
                       class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            @if ($this->activeFilters)
                <button wire:click="clearFilters"
                        class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                    Clear ({{ $this->activeFilters }})
                </button>
            @endif
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
            <p class="text-sm text-slate-600">
                @if ($logs->total() > 0)
                    Showing <span class="font-semibold text-slate-900">{{ number_format($logs->firstItem()) }}–{{ number_format($logs->lastItem()) }}</span>
                    of <span class="font-semibold text-slate-900">{{ number_format($logs->total()) }}</span>
                @else
                    No generations match these filters
                @endif
            </p>
            <div class="flex items-center gap-2">
                <label class="text-xs text-slate-500">Rows</label>
                <select wire:model.live="perPage" class="rounded-lg border-slate-300 py-1 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach ([20, 50, 100] as $n)<option value="{{ $n }}">{{ $n }}</option>@endforeach
                </select>
            </div>
        </div>
        {{-- Fixed layout with declared widths: a long product name or email was widening
             the table and pushing the whole page into a sideways scroll. --}}
        <table class="w-full table-fixed divide-y divide-slate-200 text-sm">
            <colgroup>
                <col style="width:150px">   {{-- When --}}
                <col style="width:190px">   {{-- User --}}
                <col>                       {{-- Product — takes what's left --}}
                <col style="width:110px">   {{-- Model --}}
                <col style="width:78px">    {{-- Variants --}}
                <col style="width:96px">    {{-- Copied --}}
                <col style="width:64px">    {{-- View --}}
            </colgroup>
            <thead class="bg-slate-50">
                <tr class="text-left text-xs uppercase tracking-wide text-slate-400">
                    <th class="px-4 py-3 font-semibold">When</th>
                    <th class="px-4 py-3 font-semibold">User</th>
                    <th class="px-4 py-3 font-semibold">Product</th>
                    <th class="px-4 py-3 font-semibold">Model</th>
                    <th class="px-4 py-3 font-semibold">Variants</th>
                    <th class="px-4 py-3 font-semibold">Copied</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($logs as $log)
                    @php
                        $output = $log->output ?? [];
                        $variants = is_array($output) ? ($output['variants'] ?? (array_is_list($output) ? $output : [])) : [];
                        $mainFlow = is_array($output) ? ($output['main_flow'] ?? null) : null;
                        $salesPrompt = is_array($output) ? ($output['sales_prompt'] ?? null) : null;
                        $afterSalesPrompt = is_array($output) ? ($output['aftersales_prompt'] ?? null) : null;
                        $copies = collect($log->copies ?? []);
                        $isCopied = fn ($vi, $field) => $copies->contains(fn ($c) => ($c['variant'] ?? null) === $vi && ($c['field'] ?? null) === $field);
                        $reqCount = data_get($log->input, 'variants');
                    @endphp
                    <tr class="hover:bg-slate-50 align-top">
                        <td class="px-4 py-3 text-slate-500">{{ $log->created_at->format('M d, Y g:i A') }}</td>
                        <td class="px-4 py-3">
                            <div class="truncate font-medium text-slate-800" title="{{ $log->user?->name }}">{{ $log->user?->name ?? '—' }}</div>
                            <div class="truncate text-xs text-slate-400" title="{{ $log->user?->email }}">{{ $log->user?->email }}</div>
                        </td>
                        @php $product = data_get($log->input, 'product_name', '—'); @endphp
                        <td class="px-4 py-3 text-slate-700"><div class="truncate" title="{{ $product }}">{{ $product }}</div></td>
                        <td class="px-4 py-3"><span class="block truncate rounded bg-slate-100 px-2 py-0.5 text-xs font-mono text-slate-600" title="{{ $log->model }}">{{ $log->model ?? '—' }}</span></td>
                        <td class="px-4 py-3 text-xs text-slate-500">req: {{ $reqCount ?? '—' }}<br>got: {{ count($variants) }}</td>
                        <td class="px-4 py-3">
                            @if ($copies->count())
                                <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">{{ $copies->count() }} copied</span>
                            @else
                                <span class="text-xs text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button wire:click="toggle({{ $log->id }})" class="text-xs font-semibold text-indigo-600 hover:underline">
                                {{ $expanded === $log->id ? 'Hide' : 'View' }}
                            </button>
                        </td>
                    </tr>
                    @if ($expanded === $log->id)
                        <tr class="bg-slate-50">
                            <td colspan="7" class="px-4 py-4">
                                @if ($log->status !== 'success')
                                    <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">Error: {{ $log->error }}</div>
                                @endif

                                <div class="space-y-3">
                                    @if ($mainFlow)
                                        <div class="rounded-lg border border-slate-200 bg-white p-4">
                                            <div class="text-xs font-bold text-indigo-600 mb-2">📢 MAIN FLOW @if ($isCopied(-1, 'main_flow'))<span class="ml-1 rounded-full bg-emerald-100 px-2 py-0.5 text-emerald-700">copied</span>@endif</div>
                                            <pre class="whitespace-pre-wrap break-words text-xs rounded-md p-2 {{ $isCopied(-1, 'main_flow') ? 'bg-emerald-100 ring-1 ring-emerald-300' : 'bg-slate-50' }}">{{ $mainFlow }}</pre>
                                        </div>
                                    @endif
                                    @foreach ($variants as $vi => $v)
                                        <div class="rounded-lg border border-slate-200 bg-white p-4">
                                            <div class="text-xs font-bold text-indigo-600 mb-3">VARIANT {{ $vi + 1 }}@if (! empty($v['angle'])) · <span class="text-teal-600">{{ $v['angle'] }}</span>@endif</div>
                                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 text-sm">
                                                <div>
                                                    <div class="text-[10px] uppercase tracking-wide text-slate-400 font-semibold">Primary Text</div>
                                                    <div class="mt-1 rounded-md px-2 py-1.5 whitespace-pre-wrap {{ $isCopied($vi, 'primary_text') ? 'bg-emerald-100 ring-1 ring-emerald-300' : 'bg-slate-50' }}">{{ $v['primary_text'] ?? '' }}</div>
                                                    <div class="mt-3 text-[10px] uppercase tracking-wide text-slate-400 font-semibold">Headline</div>
                                                    <div class="mt-1 rounded-md px-2 py-1.5 font-semibold {{ $isCopied($vi, 'headline') ? 'bg-emerald-100 ring-1 ring-emerald-300' : 'bg-slate-50' }}">{{ $v['headline'] ?? '' }}</div>
                                                </div>
                                                <div>
                                                    <div class="text-[10px] uppercase tracking-wide text-slate-400 font-semibold">Messaging Template</div>
                                                    <div class="mt-1 rounded-md px-2 py-1.5 whitespace-pre-wrap {{ $isCopied($vi, 'messaging_template') ? 'bg-emerald-100 ring-1 ring-emerald-300' : 'bg-slate-50' }}">{{ $v['messaging_template'] ?? '' }}</div>
                                                </div>
                                                <div>
                                                    <div class="text-[10px] uppercase tracking-wide text-slate-400 font-semibold">Quick Replies</div>
                                                    <div class="mt-1 flex flex-col gap-1.5">
                                                        @foreach ($v['quick_replies'] ?? [] as $qi => $qr)
                                                            <span class="rounded px-2 py-1 text-xs {{ $isCopied($vi, 'quick_reply_'.$qi) ? 'bg-emerald-100 ring-1 ring-emerald-300 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">{{ $qr }}</span>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach

                                    @if ($salesPrompt)
                                        <div class="rounded-lg border border-slate-200 bg-white p-4">
                                            <div class="text-xs font-bold text-indigo-600 mb-2">🤖 BOTCAKE SALES PROMPT @if ($isCopied(-1, 'sales_prompt'))<span class="ml-1 rounded-full bg-emerald-100 px-2 py-0.5 text-emerald-700">copied</span>@endif</div>
                                            <pre class="whitespace-pre-wrap break-words text-xs rounded-md p-2 max-h-72 overflow-auto {{ $isCopied(-1, 'sales_prompt') ? 'bg-emerald-100 ring-1 ring-emerald-300' : 'bg-slate-50' }}">{{ $salesPrompt }}</pre>
                                        </div>
                                    @endif

                                    @if ($afterSalesPrompt)
                                        <div class="rounded-lg border border-slate-200 bg-white p-4">
                                            <div class="text-xs font-bold text-indigo-600 mb-2">🤝 BOTCAKE AFTER-SALES PROMPT @if ($isCopied(-1, 'aftersales_prompt'))<span class="ml-1 rounded-full bg-emerald-100 px-2 py-0.5 text-emerald-700">copied</span>@endif</div>
                                            <pre class="whitespace-pre-wrap break-words text-xs rounded-md p-2 max-h-72 overflow-auto {{ $isCopied(-1, 'aftersales_prompt') ? 'bg-emerald-100 ring-1 ring-emerald-300' : 'bg-slate-50' }}">{{ $afterSalesPrompt }}</pre>
                                        </div>
                                    @endif

                                    {{-- The inputs as a table. Reading them as pretty-printed
                                         JSON meant picking field names out of braces and
                                         quotes; these are just labelled values. --}}
                                    <details class="text-xs">
                                        <summary class="cursor-pointer text-slate-500 font-medium">Inputs used</summary>
                                        @php
                                            $input = is_array($log->input) ? $log->input : [];
                                            $spf = $input['sales_prompt_fields'] ?? [];
                                            $top = collect($input)->except('sales_prompt_fields');

                                            // "product_name" / "PRODUCT_NAME" both read as "Product name".
                                            $label = fn ($k) => ucfirst(strtolower(str_replace('_', ' ', $k)));
                                        @endphp

                                        <div class="mt-2 rounded-lg border border-slate-200 bg-white overflow-hidden">
                                            <table class="w-full table-fixed text-xs">
                                                <colgroup><col style="width:190px"><col></colgroup>
                                                <tbody class="divide-y divide-slate-100">
                                                    @foreach ($top as $k => $v)
                                                        <tr class="align-top">
                                                            <th class="bg-slate-50 px-3 py-2 text-left font-semibold text-slate-500">{{ $label($k) }}</th>
                                                            <td class="px-3 py-2 text-slate-700 whitespace-pre-wrap break-words">
                                                                @if (is_array($v))
                                                                    {{ implode(', ', $v) }}
                                                                @elseif (trim((string) $v) === '')
                                                                    <span class="text-slate-300">—</span>
                                                                @else
                                                                    {{ $v }}
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach

                                                    @if (! empty($spf))
                                                        <tr>
                                                            <th colspan="2" class="bg-indigo-50 px-3 py-1.5 text-left text-[10px] font-bold uppercase tracking-wider text-indigo-700">
                                                                Sales prompt fields
                                                            </th>
                                                        </tr>
                                                        @foreach ($spf as $k => $v)
                                                            <tr class="align-top">
                                                                <th class="bg-slate-50 px-3 py-2 text-left font-semibold text-slate-500">{{ $label($k) }}</th>
                                                                <td class="px-3 py-2 text-slate-700 whitespace-pre-wrap break-words">
                                                                    @if (trim((string) $v) === '')
                                                                        <span class="text-slate-300">—</span>
                                                                    @else
                                                                        {{ $v }}
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    @endif
                                                </tbody>
                                            </table>
                                        </div>
                                    </details>
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
