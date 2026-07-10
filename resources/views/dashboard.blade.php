<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('AI Tools') }}
            </h2>
            <span class="text-sm text-gray-500">
                Plan: <strong>{{ auth()->user()->plan?->name ?? '—' }}</strong>
                · {{ auth()->user()->remainingQuota() }}/{{ auth()->user()->dailyQuota() }} left today
            </span>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            @unless (auth()->user()->apiKeyFor('openai'))
                <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    ⚠️ Wala ka pang OpenAI API key.
                    <a href="{{ route('profile') }}" class="font-semibold underline">Magdagdag sa Profile</a>
                    para magamit ang mga tools.
                </div>
            @endunless

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @forelse ($tools as $tool)
                    @php $route = $tool->slug === 'ad-copy-generator' ? route('tools.ad-copy') : null; @endphp
                    <a @if ($route) href="{{ $route }}" wire:navigate @endif
                       class="group block rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md hover:border-indigo-300 {{ $route ? '' : 'opacity-60 cursor-default' }}">
                        <div class="text-3xl mb-3">{{ $tool->icon ?? '🛠️' }}</div>
                        <h3 class="font-semibold text-gray-900 group-hover:text-indigo-600">{{ $tool->name }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ $tool->description }}</p>
                        <div class="mt-4">
                            @if ($route)
                                <span class="inline-flex items-center text-sm font-medium text-indigo-600">Open →</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs text-gray-500">Coming soon</span>
                            @endif
                        </div>
                    </a>
                @empty
                    <p class="text-gray-500">Wala pang available na tools.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
