<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-16" wire:poll.10s="refreshStatus">
    <div class="bg-white shadow-sm border border-slate-200 rounded-2xl p-8 sm:p-10 text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-3xl">
            ⏳
        </div>

        <h1 class="mt-5 text-xl font-bold text-slate-900">Account Pending Approval</h1>

        <p class="mt-2 text-sm text-slate-600">
            Thanks for signing up, <strong>{{ explode(' ', auth()->user()->name)[0] }}</strong>!
            Your account is currently <span class="font-semibold text-amber-600">pending</span>.
            An admin will review it — you'll get access to the AI tools once approved.
        </p>

        <div class="mt-5 rounded-lg bg-slate-50 border border-slate-200 px-4 py-3 text-sm text-slate-500">
            Signed in as<br>
            <span class="font-medium text-slate-700">{{ auth()->user()->email }}</span>
        </div>

        <div class="mt-6 flex items-center justify-center gap-2 text-xs text-slate-400">
            <span class="flex h-2 w-2 relative">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
            </span>
            Waiting for approval… this page updates automatically — no need to refresh.
        </div>
    </div>
</div>
