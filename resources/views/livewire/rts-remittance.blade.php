<div>
    @include('partials.rts-nav')

    <div class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-4">

        {{-- Filters + fee-rate summary --}}
        <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="flex flex-wrap items-end gap-4">
                <div class="w-40">
                    <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">From</label>
                    <x-date-field model="from" :min="$minData" :max="$maxData" size="text-sm"
                                  class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                </div>
                <div class="w-40">
                    <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">To</label>
                    <x-date-field model="to" :min="$minData" :max="$maxData" size="text-sm"
                                  class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                </div>
                @if ($minData && $maxData)
                    <div class="text-[11px] text-gray-400 self-center">Pickup dates: {{ \Illuminate\Support\Carbon::parse($minData)->format('M d, Y') }} – {{ \Illuminate\Support\Carbon::parse($maxData)->format('M d, Y') }}</div>
                @endif
                <div class="flex-1"></div>
                <div class="text-right text-xs text-gray-500">
                    <div>Current COD Fee: <strong>{{ is_numeric($codPercent) ? rtrim(rtrim(number_format($codPercent, 4), '0'), '.').'%' : '—' }}</strong>
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
                            Add your <strong>COD Fee rate</strong> and <strong>VAT rate</strong> (with an effective date) in
                            <a href="{{ route('settings') }}" wire:navigate class="underline font-semibold">Settings</a> to compute remittance.
                        </div>
                    </div>
                </div>
            </section>
        @else
            @if (! empty($uncovered))
                <section class="bg-amber-50 border border-amber-300 rounded-xl shadow-sm p-4">
                    <div class="flex items-start gap-2">
                        <span class="text-amber-600 text-lg">⚠️</span>
                        <div class="text-sm text-amber-800">
                            <span class="font-semibold">{{ count($uncovered) }} date(s) have no fee rate and were excluded</span>
                            @if ($earliestRate)
                                — your earliest rate starts <strong>{{ $earliestRate->format('M d, Y') }}</strong>, so anything before it has no rate.
                            @endif
                            Add an earlier effective date in <a href="{{ route('settings') }}" wire:navigate class="underline font-semibold">Settings</a> to include them.
                            <div class="mt-1 text-xs text-amber-700">Excluded: {{ implode(', ', array_slice($uncovered, 0, 8)) }}{{ count($uncovered) > 8 ? '…' : '' }}</div>
                        </div>
                    </div>
                </section>
            @endif
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

                        <tfoot class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 border-t text-right">TOTAL</th>
                                <th class="px-3 py-2 border-t text-right">{{ number_format($totals['delivered']) }}</th>
                                <th class="px-3 py-2 border-t text-right">₱{{ number_format($totals['cod_sum'], 2) }}</th>
                                <th class="px-3 py-2 border-t text-right">₱{{ number_format($totals['cod_fee'], 2) }}</th>
                                <th class="px-3 py-2 border-t text-right">₱{{ number_format($totals['cod_fee_vat'], 2) }}</th>
                                <th class="px-3 py-2 border-t text-right">{{ number_format($totals['picked']) }}</th>
                                <th class="px-3 py-2 border-t text-right">₱{{ number_format($totals['ship_cost'], 2) }}</th>
                                <th class="px-3 py-2 border-t text-right font-semibold">₱{{ number_format($totals['remittance'], 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="text-[11px] text-gray-500 mt-3">
                    <span class="font-semibold">Formula:</span>
                    COD Fee = <code>rate × COD sum</code> ·
                    VAT = <code>rate × COD Fee</code> ·
                    Shipping = <code>actual Total Shipping Cost</code> ·
                    Remittance = <code>COD − Fee − VAT − Shipping</code>.
                    <span class="text-gray-400">Rates are applied per date based on their effective date.</span>
                </div>
            </section>
        @endif
    </div>
</div>
