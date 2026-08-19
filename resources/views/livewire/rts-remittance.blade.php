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
                    <div>COD Fee: <strong>{{ is_numeric($codPercent) ? rtrim(rtrim(number_format($codPercent, 4), '0'), '.').'%' : '—' }}</strong>
                        · VAT: <strong>{{ is_numeric($vatPercent) ? rtrim(rtrim(number_format($vatPercent, 4), '0'), '.').'%' : '—' }}</strong></div>
                    <a href="{{ route('settings') }}" wire:navigate class="mt-1 inline-block text-indigo-600 hover:text-indigo-800 font-semibold">⚙️ Edit rates in Settings</a>
                </div>
            </div>
        </section>

        @if (! $ratesReady)
            <section class="bg-amber-50 border border-amber-300 rounded-xl shadow-sm p-4">
                <div class="flex items-start gap-2">
                    <span class="text-amber-600 text-lg">⚠️</span>
                    <div>
                        <div class="font-semibold text-amber-800">Set your fee rates first</div>
                        <div class="text-sm text-amber-700 mt-1">
                            Enter your <strong>COD Fee rate</strong> and <strong>VAT rate</strong> in
                            <a href="{{ route('settings') }}" wire:navigate class="underline font-semibold">Settings</a> to compute remittance.
                        </div>
                    </div>
                </div>
            </section>
        @else
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
                                <th class="px-3 py-2 border-b text-right">Remittance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $r)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 border-b whitespace-nowrap">{{ $r['date'] }}</td>
                                    <td class="px-3 py-2 border-b text-right">{{ number_format($r['delivered']) }}</td>
                                    <td class="px-3 py-2 border-b text-right">₱{{ number_format($r['cod_sum'], 2) }}</td>
                                    <td class="px-3 py-2 border-b text-right">₱{{ number_format($r['cod_fee'], 2) }}</td>
                                    <td class="px-3 py-2 border-b text-right">₱{{ number_format($r['cod_fee_vat'], 2) }}</td>
                                    <td class="px-3 py-2 border-b text-right">{{ number_format($r['picked']) }}</td>
                                    <td class="px-3 py-2 border-b text-right">₱{{ number_format($r['ship_cost'], 2) }}</td>
                                    <td class="px-3 py-2 border-b text-right font-semibold">₱{{ number_format($r['remittance'], 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="px-3 py-6 text-center text-gray-500">No data for the selected date(s).</td></tr>
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
                                <th class="px-3 py-2 border-t text-right">₱{{ number_format($totals['ship_cost'], 2) }}</th>
                                <th class="px-3 py-2 border-t text-right font-semibold" x-text="money(remitEff)"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="text-[11px] text-gray-500 mt-3">
                    <span class="font-semibold">Formula:</span>
                    COD Fee = <code>{{ rtrim(rtrim(number_format($codPercent, 4), '0'), '.') }}% × COD sum</code> ·
                    VAT = <code>{{ rtrim(rtrim(number_format($vatPercent, 4), '0'), '.') }}% × COD Fee</code> ·
                    Shipping = <code>actual Total Shipping Cost</code> ·
                    Remittance = <code>COD − Fee − VAT − Shipping</code>.
                    <span class="text-gray-400">Tip: you can edit the Total COD Fee / VAT above for a what-if — the Total Remittance updates live.</span>
                </div>
            </section>
        @endif
    </div>
</div>
