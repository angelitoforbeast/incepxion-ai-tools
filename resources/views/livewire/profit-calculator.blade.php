<div>
    <div class="bg-slate-50 border-b border-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <h1 class="text-2xl font-bold text-gray-900">💰 J&amp;T Profit Calculator</h1>
            <p class="text-sm text-gray-500">Compute net profit per campaign, and get suggested RTS / CPP to hit your target. Two views for comparing scenarios.</p>
        </div>
    </div>

    <div class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            @php
                $fields = [
                    'cpp'      => 'Cost Per Purchase (CPP)',
                    'cogs'     => 'Cost of Goods (COGS)',
                    'sf'       => 'Shipping Fee',
                    'orders'   => 'Number of Orders',
                    'codPrice' => 'COD Price',
                    'codFee'   => 'COD Fee (e.g. 0.02 = 2%)',
                    'rts'      => 'Estimated Returns / RTS (e.g. 0.4 = 40%)',
                ];
            @endphp

            @foreach ([1, 2] as $n)
                @php $net = ${'net'.$n}; $adj = ${'adj'.$n}; @endphp
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <h2 class="text-lg font-semibold text-gray-900 text-center mb-4">Calculator {{ $n }}</h2>

                    <div class="space-y-3">
                        @foreach ($fields as $key => $label)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
                                <input type="number" step="any" wire:model="c{{ $n }}.{{ $key }}"
                                       class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        @endforeach
                    </div>

                    <button wire:click="calcNet({{ $n }})"
                            class="mt-4 w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                        Calculate Net Profit
                    </button>

                    <div class="mt-4 text-center">
                        <span class="text-sm text-gray-500">Net Profit:</span>
                        <div class="text-2xl font-bold {{ $net !== null && $net < 0 ? 'text-red-600' : 'text-indigo-600' }}">
                            ₱{{ $net !== null ? number_format($net, 2) : '0.00' }}
                        </div>
                    </div>

                    <hr class="my-5 border-gray-100">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Target Net Profit</label>
                        <input type="number" step="any" wire:model="c{{ $n }}.target"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <button wire:click="calcAdj({{ $n }})"
                            class="mt-3 w-full rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">
                        Calculate Adjustments
                    </button>

                    @if ($adj)
                        <div class="mt-3 rounded-lg bg-gray-50 border border-gray-200 p-3 text-sm">
                            @if (isset($adj['error']))
                                <p class="text-red-600 font-medium">⚠️ {{ $adj['error'] }}</p>
                            @else
                                <p class="font-semibold text-gray-800 mb-1">Suggested Adjustments</p>
                                <p class="text-gray-700">
                                    Adjust <strong>RTS</strong> (keeping CPP fixed):
                                    <span class="font-semibold {{ $adj['rts_ok'] ? 'text-emerald-700' : 'text-red-600' }}">
                                        {{ number_format($adj['rts'], 4) }}{{ $adj['rts_ok'] ? '' : ' (out of 0–1 range)' }}
                                    </span>
                                </p>
                                <p class="text-gray-700">
                                    Adjust <strong>CPP</strong> (keeping RTS fixed):
                                    <span class="font-semibold text-indigo-700">₱{{ number_format($adj['cpp'], 2) }}</span>
                                </p>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <p class="mt-4 text-[11px] text-gray-400">
            Net Profit = orders × [ (1 − RTS) × (COD Price × (1 − COD Fee) − COGS) − (CPP + Shipping Fee) ]
        </p>
    </div>
</div>
