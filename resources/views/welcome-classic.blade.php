<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Incepxion AI — E-commerce Tools for Sellers</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-white text-slate-800">

    <!-- Nav -->
    <header class="sticky top-0 z-30 bg-white/80 backdrop-blur border-b border-slate-100">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2.5">
                <x-application-logo class="w-9 h-9" />
                <span class="font-bold text-slate-900">Incepxion AI</span>
            </a>
            <nav class="flex items-center gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900 px-3 py-2">Log in</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Get Started</a>
                @endauth
            </nav>
        </div>
    </header>

    <!-- Hero -->
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-indigo-50/60 to-white -z-10"></div>
        <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-20 pb-16 text-center">
            <span class="inline-flex items-center gap-2 rounded-full border border-indigo-100 bg-indigo-50 px-4 py-1.5 text-sm font-medium text-indigo-700">
                ⚡ AI-powered tools for e-commerce
            </span>
            <h1 class="mt-6 text-4xl sm:text-6xl font-extrabold tracking-tight text-slate-900">
                All your tools,<br>
                <span class="bg-gradient-to-r from-indigo-600 to-violet-600 bg-clip-text text-transparent">in one place.</span>
            </h1>
            <p class="mt-5 max-w-2xl mx-auto text-lg text-slate-600">
                Ad copy generator, RTS processing, profit computation — the tools online sellers need to move faster and earn more.
            </p>
            <div class="mt-8 flex items-center justify-center gap-3">
                <a href="{{ route('register') }}" class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-200 hover:bg-indigo-700">
                    Start for free →
                </a>
                <a href="{{ route('login') }}" class="rounded-xl border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    I already have an account
                </a>
            </div>
            <p class="mt-4 text-xs text-slate-400">Free to start · BYOK (bring your own API key) · No credit card</p>
        </div>
    </section>

    <!-- Tools -->
    <section class="max-w-6xl mx-auto px-4 sm:px-6 py-16">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-slate-900">Tools</h2>
            <p class="mt-2 text-slate-600">Always growing — here's what's available and what's coming.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @php
                $features = [
                    ['icon' => '📣', 'name' => 'AI Ad Copy Generator', 'desc' => 'High-converting Facebook ad copy in Taglish, Filipino, or English. 5 variants per generation.', 'ready' => true],
                    ['icon' => '📦', 'name' => 'RTS Processor', 'desc' => 'Upload your courier file (J&T, etc.) to quickly process Return-to-Sender orders.', 'ready' => false],
                    ['icon' => '💰', 'name' => 'Profit Computation', 'desc' => 'Compute profit per order/product — including shipping, fees, and COGS.', 'ready' => false],
                ];
            @endphp
            @foreach ($features as $f)
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-violet-500 text-2xl">{{ $f['icon'] }}</div>
                    <h3 class="mt-4 font-semibold text-slate-900 flex items-center gap-2">
                        {{ $f['name'] }}
                        @unless ($f['ready'])<span class="text-[10px] font-medium bg-slate-100 text-slate-500 rounded-full px-2 py-0.5">soon</span>@endunless
                    </h3>
                    <p class="mt-1.5 text-sm text-slate-500">{{ $f['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <!-- How it works -->
    <section class="bg-slate-50 border-y border-slate-100">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-16">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-slate-900">How it works</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @foreach ([
                    ['1', 'Sign up', 'With your Google account. Fast and free.'],
                    ['2', 'Add your API key', 'Your own OpenAI key (encrypted). You stay in control of costs.'],
                    ['3', 'Use the tools', 'Pick a tool, generate, done. Simple.'],
                ] as $step)
                    <div class="text-center">
                        <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-indigo-600 text-white font-bold">{{ $step[0] }}</div>
                        <h3 class="mt-4 font-semibold text-slate-900">{{ $step[1] }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $step[2] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="max-w-6xl mx-auto px-4 sm:px-6 py-20 text-center">
        <div class="rounded-3xl bg-gradient-to-br from-indigo-600 to-violet-600 px-8 py-14 shadow-xl">
            <h2 class="text-3xl font-bold text-white">Ready to level up?</h2>
            <p class="mt-3 text-indigo-100">Join the sellers using Incepxion AI.</p>
            <a href="{{ route('register') }}" class="mt-7 inline-block rounded-xl bg-white px-7 py-3 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">
                Create an account
            </a>
        </div>
    </section>

    <footer class="border-t border-slate-100">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-8 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-slate-400">
            <div class="flex items-center gap-2">
                <x-application-logo class="w-6 h-6" />
                <span class="font-semibold text-slate-600">Incepxion AI</span>
            </div>
            <p>© {{ date('Y') }} Incepxion AI. E-commerce tools for sellers.</p>
        </div>
    </footer>
</body>
</html>
