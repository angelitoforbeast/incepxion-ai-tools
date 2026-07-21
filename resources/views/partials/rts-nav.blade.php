<div class="bg-slate-50 border-b border-slate-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <h1 class="text-2xl font-bold text-gray-900">📦 RTS Processor</h1>
        <p class="text-sm text-gray-500">Upload your J&amp;T export to update shipment statuses, then monitor your RTS rates.</p>

        <div class="mt-4 flex gap-1 border-b border-slate-200">
            <a href="{{ route('tools.rts') }}" wire:navigate
               class="px-4 py-2 text-sm font-semibold -mb-px border-b-2 {{ request()->routeIs('tools.rts') ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                ⬆️ Upload &amp; Update
            </a>
            <a href="{{ route('tools.rts.monitor') }}" wire:navigate
               class="px-4 py-2 text-sm font-semibold -mb-px border-b-2 {{ request()->routeIs('tools.rts.monitor') ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                📊 RTS Monitoring
            </a>
        </div>
    </div>
</div>
