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
    <body class="font-sans text-slate-800 antialiased">
        <div class="min-h-screen flex flex-col justify-center items-center px-4 py-10
                    bg-gradient-to-br from-slate-50 via-indigo-50 to-violet-50">
            <a href="/" wire:navigate class="flex items-center gap-3 mb-6">
                <x-application-logo class="w-11 h-11" />
                <div class="leading-tight">
                    <div class="text-lg font-bold text-slate-900">Incepxion AI</div>
                    <div class="text-xs text-slate-500">E-commerce Tools</div>
                </div>
            </a>

            <div class="w-full sm:max-w-md bg-white shadow-xl shadow-indigo-100/50 rounded-2xl border border-slate-100 px-7 py-8">
                {{ $slot }}
            </div>

            <p class="mt-6 text-xs text-slate-400">© {{ date('Y') }} Incepxion AI · E-commerce tools for sellers</p>
        </div>
    </body>
</html>
