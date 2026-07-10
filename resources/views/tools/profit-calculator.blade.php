<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}" wire:navigate class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-xl font-bold text-slate-900">💰 Profit Computation</h1>
                <p class="text-sm text-slate-500">Kalkulahin ang kita, margin, at ROI ng produkto mo.</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8"
         x-data="{
            sellingPrice: 500, productCost: 200, shippingCharged: 0, shippingCost: 80,
            feePercent: 0, adCost: 50, otherCost: 0, qty: 1,
            n(v){ return parseFloat(v) || 0 },
            get revenue(){ return (this.n(this.sellingPrice) + this.n(this.shippingCharged)) * this.n(this.qty) },
            get feeAmount(){ return this.revenue * this.n(this.feePercent) / 100 },
            get totalCost(){ return (this.n(this.productCost) + this.n(this.shippingCost) + this.n(this.adCost) + this.n(this.otherCost)) * this.n(this.qty) + this.feeAmount },
            get profit(){ return this.revenue - this.totalCost },
            get profitPerUnit(){ return this.n(this.qty) > 0 ? this.profit / this.n(this.qty) : 0 },
            get margin(){ return this.revenue > 0 ? this.profit / this.revenue * 100 : 0 },
            get roi(){ return this.totalCost > 0 ? this.profit / this.totalCost * 100 : 0 },
            peso(v){ return '₱' + this.n(v).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2}) },
            pct(v){ return this.n(v).toFixed(1) + '%' }
         }">

        <div class="grid grid-cols-1 lg:grid-cols-[1fr_360px] gap-6 items-start">

            <!-- Inputs -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-400 mb-4">Input Data</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @php
                        $fields = [
                            ['sellingPrice', 'Selling Price (bawat unit)', '₱', 'Presyo na binabayad ng customer'],
                            ['productCost', 'Product Cost / COGS', '₱', 'Puhunan bawat unit'],
                            ['shippingCharged', 'Shipping na Sinisingil', '₱', 'Kung may bayad sa shipping ang customer'],
                            ['shippingCost', 'Shipping Cost (actual)', '₱', 'Aktwal na bayad sa courier'],
                            ['feePercent', 'Marketplace/Payment Fee', '%', 'hal. Shopee/Lazada commission'],
                            ['adCost', 'Ad Spend (bawat unit)', '₱', 'Gastos sa ads bawat benta'],
                            ['otherCost', 'Iba pang Gastos', '₱', 'Packaging, atbp.'],
                            ['qty', 'Quantity (units)', '×', 'Ilang units'],
                        ];
                    @endphp
                    @foreach ($fields as [$model, $label, $unit, $hint])
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">{{ $label }}</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">{{ $unit }}</span>
                                <input type="number" step="any" min="0" x-model="{{ $model }}"
                                       class="w-full rounded-lg border-slate-300 pl-8 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            </div>
                            <p class="mt-1 text-xs text-slate-400">{{ $hint }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Results -->
            <div class="space-y-4 lg:sticky lg:top-6">
                <!-- Profit hero -->
                <div class="rounded-2xl p-6 text-white shadow-lg"
                     :class="profit >= 0 ? 'bg-gradient-to-br from-emerald-500 to-teal-600' : 'bg-gradient-to-br from-rose-500 to-red-600'">
                    <p class="text-sm opacity-90">Net Profit</p>
                    <p class="mt-1 text-3xl font-extrabold" x-text="peso(profit)"></p>
                    <p class="mt-1 text-sm opacity-90">
                        <span x-text="peso(profitPerUnit)"></span> / unit
                    </p>
                </div>

                <!-- Metrics -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-xl bg-white border border-slate-200 p-4 text-center">
                        <p class="text-xs text-slate-400">Profit Margin</p>
                        <p class="mt-1 text-xl font-bold" :class="margin >= 0 ? 'text-emerald-600' : 'text-rose-600'" x-text="pct(margin)"></p>
                    </div>
                    <div class="rounded-xl bg-white border border-slate-200 p-4 text-center">
                        <p class="text-xs text-slate-400">ROI</p>
                        <p class="mt-1 text-xl font-bold" :class="roi >= 0 ? 'text-emerald-600' : 'text-rose-600'" x-text="pct(roi)"></p>
                    </div>
                </div>

                <!-- Breakdown -->
                <div class="rounded-xl bg-white border border-slate-200 p-5 text-sm">
                    <h3 class="font-semibold text-slate-700 mb-3">Breakdown</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between"><span class="text-slate-500">Revenue</span><span class="font-medium text-slate-800" x-text="peso(revenue)"></span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Marketplace fee</span><span class="text-slate-800" x-text="'−' + peso(feeAmount)"></span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Total costs</span><span class="text-slate-800" x-text="'−' + peso(totalCost)"></span></div>
                        <div class="border-t border-slate-100 pt-2 flex justify-between font-semibold">
                            <span class="text-slate-700">Net Profit</span>
                            <span :class="profit >= 0 ? 'text-emerald-600' : 'text-rose-600'" x-text="peso(profit)"></span>
                        </div>
                    </div>
                </div>
                <p class="text-xs text-slate-400 text-center">Nagko-compute agad habang nagta-type ka. Walang API key na kailangan.</p>
            </div>
        </div>
    </div>
</x-app-layout>
