@php
    $tabs = [
        ['route' => 'admin.users', 'label' => 'Users'],
        ['route' => 'admin.prompts', 'label' => 'Prompts'],
    ];
@endphp
<div class="border-b border-slate-200 mb-6">
    <nav class="flex gap-6">
        @foreach ($tabs as $t)
            <a href="{{ route($t['route']) }}" wire:navigate
               class="pb-3 -mb-px text-sm font-medium border-b-2 transition
                      {{ request()->routeIs($t['route']) ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                {{ $t['label'] }}
            </a>
        @endforeach
    </nav>
</div>
