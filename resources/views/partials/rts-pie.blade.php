@php $stop2 = $pctRts + $pctDelivered; @endphp
<div class="flex flex-wrap items-center gap-6">
    <div class="relative" style="width:150px;height:150px;flex-shrink:0;">
        <div style="width:150px;height:150px;border-radius:9999px;background:conic-gradient(#dc2626 0 {{ $pctRts }}%, #16a34a {{ $pctRts }}% {{ $stop2 }}%, #2563eb {{ $stop2 }}% 100%);"></div>
        <div class="absolute inset-0 flex items-center justify-center">
            <div style="width:84px;height:84px;background:#fff;border-radius:9999px;" class="flex flex-col items-center justify-center shadow-inner">
                <span class="text-lg font-bold text-gray-900">{{ number_format($total) }}</span>
                <span class="text-[10px] text-gray-400 uppercase tracking-wide">Total</span>
            </div>
        </div>
    </div>
    <div class="space-y-2 text-sm">
        <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-sm" style="background:#dc2626;"></span>
            <span class="text-gray-700 w-20">RTS</span>
            <span class="font-bold text-red-600">{{ number_format($pctRts, 1) }}%</span>
            <span class="text-xs text-gray-400">({{ number_format($totalRts) }})</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-sm" style="background:#16a34a;"></span>
            <span class="text-gray-700 w-20">Delivered</span>
            <span class="font-bold text-green-600">{{ number_format($pctDelivered, 1) }}%</span>
            <span class="text-xs text-gray-400">({{ number_format($totalDelivered) }})</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-sm" style="background:#2563eb;"></span>
            <span class="text-gray-700 w-20">In Transit</span>
            <span class="font-bold text-blue-600">{{ number_format($pctTransit, 1) }}%</span>
            <span class="text-xs text-gray-400">({{ number_format($totalTransit) }})</span>
        </div>
    </div>
</div>
