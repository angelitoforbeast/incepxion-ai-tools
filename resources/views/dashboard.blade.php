<x-app-layout title="Dashboard">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-slate-900">Hi, {{ explode(' ', auth()->user()->name)[0] }}! 👋</h1>
                <p class="text-sm text-slate-500">Choose the tool you want to use.</p>
            </div>
            <div class="text-sm text-slate-500">
                Plan: <strong class="text-slate-700">{{ auth()->user()->plan?->name ?? '—' }}</strong>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        @unless (auth()->user()->apiKeyFor('openai'))
            <div class="mb-6 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3.5">
                <span class="text-xl">🔑</span>
                <div class="text-sm text-amber-800">
                    <strong>One more step!</strong> You have no OpenAI API key yet.
                    <a href="{{ route('settings') }}" class="font-semibold underline" wire:navigate>Add it in Settings</a>
                    to use the AI tools.
                </div>
            </div>
        @endunless

        @php $grouped = $tools->groupBy(fn ($t) => $t->category ?? 'Other'); @endphp

        @forelse ($grouped as $category => $items)
            <div class="mb-8">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3">{{ $category }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach ($items as $tool)
                        @php $route = match ($tool->slug) {
                            'ad-copy-generator'  => route('tools.ad-copy'),
                            'rts-processor'      => route('tools.rts'),
                            'profit-computation' => route('tools.profit'),
                            default              => null,
                        }; @endphp
                        <a @if ($route) href="{{ $route }}" wire:navigate @endif
                           class="group relative flex flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition
                                  {{ $route ? 'hover:shadow-md hover:-translate-y-0.5 hover:border-indigo-300' : '' }}">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-violet-500 text-2xl shadow-sm">
                                {{ $tool->icon ?? '🛠️' }}
                            </div>
                            <h3 class="mt-4 font-semibold text-slate-900 {{ $route ? 'group-hover:text-indigo-600' : '' }}">{{ $tool->name }}</h3>
                            <p class="mt-1 flex-1 text-sm text-slate-500">{{ $tool->description }}</p>
                            <div class="mt-4">
                                @if ($route)
                                    <span class="inline-flex items-center gap-1 text-sm font-semibold text-indigo-600">
                                        Open
                                        <svg class="w-4 h-4 transition group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-500">🔜 Coming soon</span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="text-slate-500">No tools available yet.</p>
        @endforelse
    </div>
</x-app-layout>
