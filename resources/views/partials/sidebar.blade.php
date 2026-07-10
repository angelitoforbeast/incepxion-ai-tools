@php
    $nav = [
        ['route' => 'dashboard', 'label' => 'Tools', 'icon' => 'M4 5h6v6H4V5zm10 0h6v6h-6V5zM4 15h6v4H4v-4zm10 0h6v4h-6v-4z'],
    ];
    $user = auth()->user();
@endphp

<!-- Overlay (mobile) -->
<div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"
     class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden" style="display:none"></div>

<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
       class="fixed inset-y-0 left-0 z-40 w-64 transform bg-slate-900 text-slate-300 transition-transform duration-200 lg:translate-x-0 flex flex-col">

    <!-- Brand -->
    <div class="flex items-center gap-3 px-5 h-16 border-b border-slate-800">
        <x-application-logo class="w-9 h-9" />
        <div class="leading-tight">
            <div class="text-white font-bold text-sm">Incepxion AI</div>
            <div class="text-[11px] text-slate-400">E-commerce Tools</div>
        </div>
    </div>

    <!-- Nav -->
    <nav class="flex-1 px-3 py-4 space-y-1">
        <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Menu</p>
        @foreach ($nav as $item)
            @php $active = request()->routeIs($item['route']); @endphp
            <a href="{{ route($item['route']) }}" wire:navigate
               class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition
                      {{ $active ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                </svg>
                {{ $item['label'] }}
            </a>
        @endforeach

        @if ($user->isAdmin())
            @php $adminActive = request()->routeIs('admin.*'); @endphp
            <a href="{{ route('admin.users') }}" wire:navigate
               class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition
                      {{ $adminActive ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Admin
            </a>
        @endif
    </nav>

    <!-- Plan usage -->
    <div class="px-4 pb-3">
        <div class="rounded-lg bg-slate-800/60 p-3">
            <div class="flex items-center justify-between text-xs text-slate-400">
                <span>{{ $user->plan?->name ?? 'Free' }} plan</span>
                <span>{{ $user->remainingQuota() }}/{{ $user->dailyQuota() }}</span>
            </div>
            @php $pct = $user->dailyQuota() > 0 ? min(100, round($user->usageToday() / $user->dailyQuota() * 100)) : 0; @endphp
            <div class="mt-2 h-1.5 w-full rounded-full bg-slate-700">
                <div class="h-1.5 rounded-full bg-indigo-500" style="width: {{ $pct }}%"></div>
            </div>
        </div>
    </div>

    <!-- Account (click to reveal Profile & Logout) -->
    <div class="border-t border-slate-800 p-3" x-data="{ accountOpen: false }" @click.outside="accountOpen = false">

        <!-- Dropdown menu (pops up on click) -->
        <div x-show="accountOpen" x-transition style="display:none"
             class="mb-2 rounded-lg bg-slate-800 border border-slate-700 overflow-hidden shadow-lg">
            <a href="{{ route('profile') }}" wire:navigate
               class="flex items-center gap-3 px-3 py-2.5 text-sm text-slate-200 hover:bg-slate-700 hover:text-white transition">
                <svg style="width:18px;height:18px" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Profile
            </a>
            <a href="{{ route('settings') }}" wire:navigate
               class="flex items-center gap-3 px-3 py-2.5 text-sm text-slate-200 hover:bg-slate-700 hover:text-white transition border-t border-slate-700/60">
                <svg style="width:18px;height:18px" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Settings
            </a>
            <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-700/60">
                @csrf
                <button type="submit"
                        class="w-full flex items-center gap-3 px-3 py-2.5 text-sm text-slate-200 hover:bg-slate-700 hover:text-white transition text-left">
                    <svg style="width:18px;height:18px" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Log out
                </button>
            </form>
        </div>

        <!-- Account chip (button) -->
        <button @click="accountOpen = !accountOpen"
                class="w-full flex items-center gap-3 rounded-lg p-1.5 hover:bg-slate-800 transition">
            @if ($user->avatar)
                <img src="{{ $user->avatar }}" alt="" class="w-9 h-9 rounded-full object-cover">
            @else
                <div class="w-9 h-9 rounded-full bg-indigo-500 text-white flex items-center justify-center text-sm font-semibold">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
            @endif
            <div class="min-w-0 flex-1 text-left">
                <div class="text-sm font-medium text-white truncate">{{ $user->name }}</div>
                <div class="text-[11px] text-slate-400 truncate">{{ $user->email }}</div>
            </div>
            <svg class="w-4 h-4 text-slate-400 transition" :class="accountOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
    </div>
</aside>
