@php
    $tabs = [
        ['route' => 'admin.users', 'label' => 'Users'],
        ['route' => 'admin.prompts', 'label' => 'Prompts'],
        ['route' => 'admin.courses', 'label' => 'Courses'],
        ['route' => 'admin.logs', 'label' => 'Data Logs'],
    ];
@endphp
<div class="mb-6 inline-flex rounded-xl border border-slate-200 bg-white p-1 shadow-sm">
    @foreach ($tabs as $t)
        <a href="{{ route($t['route']) }}" wire:navigate
           class="rounded-lg px-4 py-2 text-sm font-semibold transition
                  {{ request()->routeIs($t['route']) ? 'bg-indigo-600 text-white shadow' : 'text-slate-600 hover:bg-slate-100' }}">
            {{ $t['label'] }}
        </a>
    @endforeach
</div>
