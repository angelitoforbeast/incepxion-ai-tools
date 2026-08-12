<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-16" wire:poll.15s="refreshStatus">
    <div class="bg-white shadow-sm border border-rose-100 rounded-2xl p-8 sm:p-10 text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-rose-100 text-3xl">
            @if ($user->status === 'suspended') ⏸️ @else 🚫 @endif
        </div>

        @if ($user->status === 'suspended')
            <h1 class="mt-5 text-xl font-bold text-slate-900">Account Suspended</h1>
            <p class="mt-2 text-sm text-slate-600">
                Your access has been paused by an admin. If you think this is a mistake, please contact us.
            </p>
        @else
            <h1 class="mt-5 text-xl font-bold text-slate-900">Account Not Approved</h1>
            <p class="mt-2 text-sm text-slate-600">
                Sorry, <strong>{{ explode(' ', $user->name)[0] }}</strong> — your account request was not approved.
            </p>
            @if ($user->remarks)
                <div class="mt-4 rounded-lg bg-rose-50 border border-rose-200 px-4 py-3 text-left text-sm text-rose-800">
                    <span class="font-semibold">Reason:</span> {{ $user->remarks }}
                </div>
            @endif
        @endif

        <div class="mt-5 rounded-lg bg-slate-50 border border-slate-200 px-4 py-3 text-sm text-slate-500">
            Signed in as<br>
            <span class="font-medium text-slate-700">{{ $user->email }}</span>
        </div>

        <form method="POST" action="{{ route('logout') }}" class="mt-6">
            @csrf
            <button type="submit" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                Log out
            </button>
        </form>
    </div>
</div>
