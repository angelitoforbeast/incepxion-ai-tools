<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ isset($title) ? $title.' — Incepxion' : 'Incepxion AI Tools' }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-800 antialiased">
        <div class="min-h-screen flex flex-col justify-center items-center px-4 py-10
                    bg-gradient-to-br from-slate-50 via-indigo-50 to-violet-50">
            <a href="/" wire:navigate class="mb-7">
                <x-brand class="h-10" />
            </a>

            <div class="w-full sm:max-w-md bg-white shadow-xl shadow-indigo-100/50 rounded-2xl border border-slate-100 px-7 py-8">
                {{ $slot }}
            </div>

            <p class="mt-6 text-xs text-slate-400">© {{ date('Y') }} Incepxion AI · E-commerce tools for sellers</p>
        </div>
    </body>
</html>
