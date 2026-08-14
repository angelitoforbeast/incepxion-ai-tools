<div>
    @include('partials.rts-nav')

    <div class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-4">

        {{-- Filters + fee-rate summary --}}
        <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">From</label>
                    <input type="date" wire:model.live="from" class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">To</label>
                    <input type="date" wire:model.live="to" class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="flex-1"></div>
                <div class="text-right text-xs text-gray-500">
                    <div>COD Fee: <strong>{{ is_numeric($codFeePercent) ? rtrim(rtrim(number_format($codFeePercent, 4), '0'), '.').'%' : '—' }}</strong>
                        · VAT: <strong>{{ is_numeric($codVatPercent) ? rtrim(rtrim(number_format($codVatPercent, 4), '0'), '.').'%' : '—' }}</strong></div>
                    <button wire:click="toggleFees" class="mt-1 text-indigo-600 hover:text-indigo-800 font-semibold">⚙️ Edit fee rates</button>
                </div>
            </div>

            {{-- Fee-rate editor --}}
            @if ($showFees)
                <div class="mt-4 border-t border-gray-100 pt-4">
                    <p class="text-sm font-semibold text-gray-700 mb-2">Your J&amp;T fee rates</p>
                    <div class="grid sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">COD Fee rate (%)</label>
                            <input type="number" step="0.0001" min="0" max="100" wire:model="codFeePercent" placeholder="e.g. 2"
                                   class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('codFeePercent') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">VAT rate (%) <span class="text-gray-400">on COD fee</span></label>
                            <input type="number" step="0.0001" min="0" max="100" wire:model="codVatPercent" placeholder="e.g. 12"
                                   class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('codVatPercent') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Expected shipping fee <span class="text-gray-400">(optional — anomaly check)</span></label>
                            <input type="number" step="0.01" min="0" wire:model="shipFee" placeholder="e.g. 50"
                                   class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('shipFee') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-2">
                        <button wire:click="saveFees" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Save rates</button>
                        <button wire:click="toggleFees" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50">Close</button>
                    </div>
                    <p class="mt-2 text-xs text-gray-400">Shipping is taken <strong>actual from your data</strong> (Total Shipping Cost). The expected shipping fee is only used to flag anomalies.</p>
                </div>
            @endif
        </section>

        @if (! $ratesReady)
            {{-- Rates not configured --}}
            <section class="bg-amber-50 border border-amber-300 rounded-xl shadow-sm p-4">
                <div class="flex items-start gap-2">
                    <span class="text-amber-600 text-lg">⚠️</span>
                    <div>
                        <div class="font-semibold text-amber-800">Set your fee rates first</div>
                        <div class="text-sm text-amber-700 mt-1">
                            Enter your <strong>COD Fee rate</strong> and <strong>VAT rate</strong> above (⚙️ Edit fee rates) to compute remittance.
                        </div>
                    </div>
                </div>
            </section>
        @else
            {{-- SF anomaly alert --}}
            @if (($totals['anomaly'] ?? 0) > 0)
                <section class="bg-red-50 border border-red-300 rounded-xl shadow-sm p-4">
                    <div class="flex items-start gap-2">
                        <span class="text-red-600 text-lg">⚠️</span>
                        <div>
                            <div class="font-semibold text-red-800">Shipping Fee Anomaly Detected</div>
                            <div class="text-sm text-red-700 mt-1">
                                <strong>{{ number_format($totals['anomaly']) }}</strong> order(s) have a shipping fee that doesn't match your expected
                                @if ($expectedSF !== null) <strong>₱{{ number_format($expectedSF, 2) }}</strong> @endif. See the red rows below.
                            </div>
                        </div>
                    </div>
                </section>
            @endif

            {{-- Table --}}
            <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <div class="flex items-center justify-between mb-2">
                    <div class="font-semibold text-gray-800">Remittance summary</div>
                    <div class="text-xs text-gray-500">Delivered by <em>SigningTime</em> · Pickups by <em>Submission Time</em> · Shipping: <strong>actual</strong></div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border border-gray-200 text-xs">
                        <thead class="bg-gray-50">
                            <tr class="text-left">
                                <th class="px-3 py-2 border-b">Date</th>
                                <th class="px-3 py-2 border-b text-right">Delivered</th>
                                <th class="px-3 py-2 border-b text-right">COD Sum</th>
                                <th class="px-3 py-2 border-b text-right">COD Fee</th>
                                <th class="px-3 py-2 border-b text-right">COD Fee VAT</th>
                                <th class="px-3 py-2 border-b text-right">Picked up</th>
                                <th class="px-3 py-2 border-b text-right">Shipping</th>
                                <th class="px-3 py-2 border-b text-right">SF</th>
                                <th class="px-3 py-2 border-b text-right">Remittance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $r)
                                @php $bad = ($r['anomaly_count'] ?? 0) > 0; @endphp
                                <tr class="{{ $bad ? 'bg-red-50 hover:bg-red-100' : 'hover:bg-gray-50' }}">
                                    <td class="px-3 py-2 border-b whitespace-nowrap">{{ $r['date'] }}</td>
                                    <td class="px-3 py-2 border-b text-right">{{ number_format($r['delivered']) }}</td>
                                    <td class="px-3 py-2 border-b text-right">₱{{ number_format($r['cod_sum'], 2) }}</td>
                                    <td class="px-3 py-2 border-b text-right">₱{{ number_format($r['cod_fee'], 2) }}</td>
                                    <td class="px-3 py-2 border-b text-right">₱{{ number_format($r['cod_fee_vat'], 2) }}</td>
                                    <td class="px-3 py-2 border-b text-right">{{ number_format($r['picked']) }}</td>
                                    <td class="px-3 py-2 border-b text-right {{ $bad ? 'text-red-700 font-semibold' : '' }}">₱{{ number_format($r['ship_cost'], 2) }}</td>
                                    <td class="px-3 py-2 border-b text-right text-[11px]">
                                        @if ($bad)
                                            <span class="text-red-700 font-semibold">⚠️ {{ $r['anomaly_count'] }}</span>
                                        @elseif ($expectedSF !== null)
                                            <span class="text-green-700">✅</span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 border-b text-right font-semibold">₱{{ number_format($r['remittance'], 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="px-3 py-6 text-center text-gray-500">No data for the selected date(s).</td></tr>
                            @endforelse
                        </tbody>

                        <tfoot class="bg-gray-50"
                               wire:key="remit-totals-{{ $from }}-{{ $to }}-{{ $totals['cod_sum'] }}-{{ $totals['ship_cost'] }}"
                               x-data="{
                                   codSum: {{ json_encode($totals['cod_sum']) }},
                                   codFeeDefault: {{ json_encode($totals['cod_fee']) }},
                                   codFeeVatDefault: {{ json_encode($totals['cod_fee_vat']) }},
                                   shipCost: {{ json_encode($totals['ship_cost']) }},
                                   codFeeInput: '',
                                   codFeeVatInput: '',
                                   init() { this.codFeeInput = this.codFeeDefault.toFixed(2); this.codFeeVatInput = this.codFeeVatDefault.toFixed(2); },
                                   num(s) { const v = parseFloat(String(s).replace(/[^0-9.\-]/g, '')); return isNaN(v) ? null : v; },
                                   get codFeeEff() { const v = this.num(this.codFeeInput); return v === null ? this.codFeeDefault : v; },
                                   get codFeeVatEff() { const v = this.num(this.codFeeVatInput); return v === null ? this.codFeeVatDefault : v; },
                                   get remitEff() { return this.codSum - this.codFeeEff - this.codFeeVatEff - this.shipCost; },
                                   money(v) { return '₱' + Number(v || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
                               }" x-init="init()">
                            <tr>
                                <th class="px-3 py-2 border-t text-right">TOTAL</th>
                                <th class="px-3 py-2 border-t text-right">{{ number_format($totals['delivered']) }}</th>
                                <th class="px-3 py-2 border-t text-right">₱{{ number_format($totals['cod_sum'], 2) }}</th>
                                <th class="px-3 py-2 border-t text-right">
                                    <input type="text" inputmode="decimal" x-model="codFeeInput" @blur="codFeeInput = (num(codFeeInput) ?? codFeeDefault).toFixed(2)"
                                           class="w-28 border rounded px-2 py-1 text-right text-xs">
                                </th>
                                <th class="px-3 py-2 border-t text-right">
                                    <input type="text" inputmode="decimal" x-model="codFeeVatInput" @blur="codFeeVatInput = (num(codFeeVatInput) ?? codFeeVatDefault).toFixed(2)"
                                           class="w-28 border rounded px-2 py-1 text-right text-xs">
                                </th>
                                <th class="px-3 py-2 border-t text-right">{{ number_format($totals['picked']) }}</th>
                                <th class="px-3 py-2 border-t text-right {{ ($totals['anomaly'] ?? 0) > 0 ? 'text-red-700' : '' }}">₱{{ number_format($totals['ship_cost'], 2) }}</th>
                                <th class="px-3 py-2 border-t text-right">
                                    @if (($totals['anomaly'] ?? 0) > 0)<span class="text-red-700 font-semibold">⚠️ {{ $totals['anomaly'] }}</span>@else<span class="text-gray-400">—</span>@endif
                                </th>
                                <th class="px-3 py-2 border-t text-right font-semibold" x-text="money(remitEff)"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="text-[11px] text-gray-500 mt-3">
                    <span class="font-semibold">Formula:</span>
                    COD Fee = <code>{{ rtrim(rtrim(number_format($codFeePercent, 4), '0'), '.') }}% × COD sum</code> ·
                    VAT = <code>{{ rtrim(rtrim(number_format($codVatPercent, 4), '0'), '.') }}% × COD Fee</code> ·
                    Shipping = <code>actual Total Shipping Cost</code> ·
                    Remittance = <code>COD − Fee − VAT − Shipping</code>.
                    <span class="text-gray-400">Tip: you can edit the Total COD Fee / VAT above for a what-if — the Total Remittance updates live.</span>
                </div>
            </section>
        @endif
    </div>
</div>
