<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-xl font-bold text-slate-900 mb-1">Admin</h1>
    @include('partials.admin-nav')

    <div class="mb-4">
        <h2 class="text-lg font-semibold text-slate-900">Billing / Settle page</h2>
        <p class="text-sm text-slate-500">What expired users see on the “Settle your account” page. Payments are handled manually — send these details, users pay offline, then you extend their access.</p>
    </div>

    @if (session('msg'))
        <div class="mb-4 max-w-2xl rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">{{ session('msg') }}</div>
    @endif

    <form wire:submit="save" class="max-w-2xl rounded-2xl border border-slate-200 bg-white shadow-sm p-6 space-y-5">
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Message</label>
            <textarea wire:model="message" rows="3" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            <p class="mt-1 text-xs text-slate-400">Shown at the top of the settle page. Line breaks are preserved.</p>
            @error('message') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">GCash <span class="font-normal text-slate-400">(optional)</span></label>
                <textarea wire:model="gcash" rows="2" placeholder="e.g. 0917 123 4567 — Juan D." class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                @error('gcash') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Bank <span class="font-normal text-slate-400">(optional)</span></label>
                <textarea wire:model="bank" rows="2" placeholder="e.g. BPI 1234-5678-90 — Juan Dela Cruz" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                @error('bank') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Contact <span class="font-normal text-slate-400">(optional)</span></label>
            <textarea wire:model="contact" rows="2" placeholder="e.g. Messenger m.me/yourpage or 0917 123 4567" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            <p class="mt-1 text-xs text-slate-400">Where users send proof of payment after paying.</p>
            @error('contact') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-3 pt-1">
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                <span wire:loading.remove wire:target="save">Save details</span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
            <a href="{{ route('settle') }}" target="_blank" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">Preview settle page ↗</a>
        </div>
    </form>
</div>
