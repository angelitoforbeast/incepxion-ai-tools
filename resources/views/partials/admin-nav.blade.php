@php
    $tabs = [
        ['route' => 'admin.users', 'label' => 'Users'],
        ['route' => 'admin.prompts', 'label' => 'Prompts'],
        ['route' => 'admin.courses', 'label' => 'Courses'],
        ['route' => 'admin.profit', 'label' => 'Profit Log'],
        ['route' => 'admin.access', 'label' => 'Access Log'],
        ['route' => 'admin.billing', 'label' => 'Billing'],
        ['route' => 'admin.logs', 'label' => 'Data Logs'],
    ];
    // $activeTab is passed by each admin component so the highlight survives Livewire
    // updates (during a livewire/update request, request()->routeIs() no longer matches).
    $activeTab = $activeTab ?? null;
@endphp
<div class="mb-6 inline-flex rounded-xl border border-slate-200 bg-white p-1 shadow-sm">
    @foreach ($tabs as $t)
        @php $isActive = $activeTab ? ($t['route'] === $activeTab) : request()->routeIs($t['route']); @endphp
        <a href="{{ route($t['route']) }}" wire:navigate
           class="rounded-lg px-4 py-2 text-sm font-semibold transition
                  {{ $isActive ? 'bg-indigo-600 text-white shadow' : 'text-slate-600 hover:bg-slate-100' }}">
            {{ $t['label'] }}
        </a>
    @endforeach
</div>
