<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Incepxion AI') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-slate-50 text-slate-800">
        <div x-data="{ sidebarOpen: false }" class="min-h-screen">

            @include('partials.sidebar')

            <div class="lg:pl-64">
                <!-- Top bar (mobile) -->
                <div class="lg:hidden sticky top-0 z-20 flex items-center gap-3 h-14 px-4 bg-white border-b border-slate-200">
                    <button @click="sidebarOpen = true" class="text-slate-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <div class="flex items-center gap-2">
                        <x-application-logo class="w-7 h-7" />
                        <span class="font-bold text-slate-900">Incepxion AI</span>
                    </div>
                </div>

                @isset($header)
                    <header class="bg-white border-b border-slate-200">
                        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
