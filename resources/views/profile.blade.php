<x-app-layout title="Profile">
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Profile</h1>
            <p class="text-sm text-slate-500">Your account information.</p>
        </div>
    </x-slot>

    @php $user = auth()->user(); $key = $user->apiKeyFor('openai'); @endphp

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        <!-- Identity card -->
        <div class="bg-white shadow-sm border border-slate-200 rounded-2xl p-6 sm:p-8">
            <div class="flex items-center gap-4">
                @if ($user->avatar)
                    <img src="{{ $user->avatar }}" alt="" class="w-16 h-16 rounded-full object-cover">
                @else
                    <div class="w-16 h-16 rounded-full bg-indigo-500 text-white flex items-center justify-center text-2xl font-semibold">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                @endif
                <div>
                    <h2 class="text-lg font-bold text-slate-900">{{ $user->name }}</h2>
                    <p class="text-sm text-slate-500">{{ $user->email }}</p>
                    @if ($user->isAdmin())
                        <span class="mt-1 inline-flex rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-700">Admin</span>
                    @endif
                </div>
            </div>

            <dl class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 border-t border-slate-100 pt-6 text-sm">
                <div>
                    <dt class="text-slate-400">Plan</dt>
                    <dd class="font-medium text-slate-800">{{ $user->plan?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-400">Status</dt>
                    <dd class="font-medium text-emerald-600">{{ ucfirst($user->status) }}</dd>
                </div>
                <div>
                    <dt class="text-slate-400">Member since</dt>
                    <dd class="font-medium text-slate-800">{{ $user->created_at->format('M d, Y') }}</dd>
                </div>
            </dl>
        </div>

        <!-- API key status -->
        <div class="bg-white shadow-sm border border-slate-200 rounded-2xl p-6 sm:p-8">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h3 class="font-semibold text-slate-900">OpenAI API Key</h3>
                    @if ($key)
                        <p class="mt-1 text-sm text-slate-500">Set up: <span class="font-mono">{{ $key->masked() }}</span></p>
                    @else
                        <p class="mt-1 text-sm text-amber-600">Not set yet — required for the AI tools.</p>
                    @endif
                </div>
                <a href="{{ route('settings') }}" wire:navigate
                   class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 whitespace-nowrap">
                    {{ $key ? 'Manage' : 'Add key' }}
                </a>
            </div>
        </div>

    </div>
</x-app-layout>
