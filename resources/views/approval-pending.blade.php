<x-guest-layout>
    <div class="text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-3xl">
            ⏳
        </div>

        <h1 class="mt-5 text-xl font-bold text-slate-900">Naghihintay ng Approval</h1>

        <p class="mt-2 text-sm text-slate-600">
            Salamat sa pag-sign up, <strong>{{ explode(' ', auth()->user()->name)[0] }}</strong>!
            Naka-<span class="font-semibold text-amber-600">pending</span> pa ang account mo.
            Susuriin ito ng admin — makakatanggap ka ng access sa mga AI tools kapag na-approve na.
        </p>

        <div class="mt-5 rounded-lg bg-slate-50 border border-slate-200 px-4 py-3 text-sm text-slate-500">
            Naka-sign in bilang<br>
            <span class="font-medium text-slate-700">{{ auth()->user()->email }}</span>
        </div>

        <form method="POST" action="{{ route('logout') }}" class="mt-6">
            @csrf
            <button type="submit"
                    class="w-full rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                Log Out
            </button>
        </form>

        <p class="mt-4 text-xs text-slate-400">
            May tanong? I-refresh ang page mamaya para makita kung na-approve ka na.
        </p>
    </div>
</x-guest-layout>
