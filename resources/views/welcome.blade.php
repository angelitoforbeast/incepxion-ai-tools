@php
    // The 15 training modules, straight from the roadmap.
    $modules = [
        ['n' => '01', 'title' => 'Andromeda Update', 'tag' => 'Full Scaling Strategy', 'icon' => '🌌',
         'desc' => 'Master the latest Andromeda system and scaling approach. Know when to scale, when to stop, and how to protect your ads from dropping performance. Identify which ads still have room to scale.'],
        ['n' => '02', 'title' => 'Botcake Full Setup', 'tag' => 'Complete AI Automation', 'icon' => '🤖',
         'desc' => 'Build a complete Botcake automation system using AI. Automate conversations, orders, and follow-ups to save time, reduce manpower, and keep orders moving smoothly.'],
        ['n' => '03', 'title' => 'Botcake Advanced Setup', 'tag' => 'Date Notice & Follow-up Automation', 'icon' => '📅',
         'desc' => 'Add Current Date Notices and automated follow-up sequences that keep customers engaged and reduce missed orders without manual checking.'],
        ['n' => '04', 'title' => 'Campaign Objectives + POS Pancake', 'tag' => 'Meta Purchase Setup', 'icon' => '🎯',
         'desc' => 'Understand Meta campaign objectives and the right way to set up POS Pancake for purchase campaigns to send stronger signals and maximize results.'],
        ['n' => '05', 'title' => 'Creative Sourcing', 'tag' => 'Search Engine + Spy Tools', 'icon' => '🔍',
         'desc' => 'Find winning creatives faster. Learn what to search for, where to look, and how to use search engines and spy tools to discover creatives that fit your product, market, and offer.'],
        ['n' => '06', 'title' => 'New vs. Old Creatives', 'tag' => 'Complete Strategy', 'icon' => '⚔️',
         'desc' => 'Know when to use proven old creatives and when to introduce new ones. Learn how market changes, seasonality, and competition affect performance and creative decisions.'],
        ['n' => '07', 'title' => 'AI Creatives', 'tag' => 'Complete Execution Guide', 'icon' => '✨',
         'desc' => 'Create high-performing ads faster with AI — from concept and script to final video. Learn how to test AI creatives the right way and avoid wasting money.'],
        ['n' => '08', 'title' => 'Product Hunt', 'tag' => 'From Discovery to Running Ads', 'icon' => '📦',
         'desc' => 'A complete workflow from finding the right products to launching profitable ad campaigns the right way. Turn opportunity into actual sales.'],
        ['n' => '09', 'title' => 'The Evian Method', 'tag' => 'Understanding Ads Before Scaling', 'icon' => '💧',
         'desc' => 'Understand Creative Budget Capacity and Ceiling Capacity using the bottle and water analogy. Learn the limits of a creative and why more budget doesn\'t always mean more results.'],
        ['n' => '10', 'title' => 'Messaging Metrics', 'tag' => 'Profit-Based Analysis', 'icon' => '📊',
         'desc' => 'Use metrics, target profit, and messaging costs to know the real performance and scaling potential of your ads. Predict CPP, recalculate conversion rate needs, and make data-driven decisions.'],
        ['n' => '11', 'title' => 'Creative Scaling', 'tag' => 'Scale Through More Winners, Not Bigger Budgets', 'icon' => '🚀',
         'desc' => 'Learn what to do when budget scaling stops. Scale by building more winning creatives and multiply profits without killing performance.'],
        ['n' => '12', 'title' => 'AI Content Removal', 'tag' => 'Image Source Code & Digital Footprint', 'icon' => '🕵️',
         'desc' => 'Remove AI indicators and analyze image file data, structure, metadata, and digital footprint. Understand what\'s really inside the file — beyond what meets the eye.'],
        ['n' => '13', 'title' => 'Profitability', 'tag' => 'J&T Profit Calculator & Target Profit', 'icon' => '🧮',
         'desc' => 'Calculate real profit, compare scenarios, and understand how costs, fees, and returns affect your final numbers. Use target profit to adjust and reach your desired profitability.'],
        ['n' => '14', 'title' => 'Actual Scenario', 'tag' => 'Messaging Metrics in Real Campaigns', 'icon' => '📈',
         'desc' => 'Apply metrics concepts in real campaigns. Analyze welcome rate, conversion rate, cost per message, purchases, and CPP to find the real problem and improve what matters.'],
        ['n' => '15', 'title' => 'Create Website', 'tag' => 'Using Manus or GPT', 'icon' => '🌐',
         'desc' => 'Build a functional website using AI with the right prompt. No coding, no complicated setup — just describe, ask, and get the result you want.'],
    ];

    $pillars = [
        ['icon' => '🎯', 'title' => 'Strategy that works',   'desc' => 'Proven frameworks that actually drive results.'],
        ['icon' => '⚙️', 'title' => 'Systems that automate', 'desc' => 'Less manual work. More output. Stronger operations.'],
        ['icon' => '📊', 'title' => 'Data that makes sense', 'desc' => 'Clear insights that lead to better decisions.'],
        ['icon' => '💰', 'title' => 'Profit that scales',    'desc' => 'Sustainable growth without sacrificing profitability.'],
        ['icon' => '🤝', 'title' => 'Community that grows',  'desc' => 'Learn. Share. Implement. Scale together.'],
    ];

    $fb = 'https://www.facebook.com/uvnis92jfzsg';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#05030a">
    <title>Incepxion — Build Systems That Print Profit</title>
    <meta name="description" content="The complete Incepxion system: 15 modules from product to profit. Strategy, automation, and data for Filipino e-commerce sellers.">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800|oswald:500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --violet:#8a36ff; --purple:#5f18d8; --pink:#ff2c78;
        }
        body {
            background:
                radial-gradient(circle at 15% 8%, rgba(109,21,222,.22), transparent 32%),
                radial-gradient(circle at 85% 12%, rgba(255,44,120,.12), transparent 28%),
                linear-gradient(180deg, #030207 0%, #080311 45%, #030207 100%);
            background-attachment: fixed;
        }
        /* Faint grid, fading out down the page so it never fights the content. */
        body::before {
            content:""; position:fixed; inset:0; pointer-events:none; z-index:0; opacity:.2;
            background-image:
                linear-gradient(rgba(255,255,255,.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.03) 1px, transparent 1px);
            background-size:52px 52px;
            -webkit-mask-image: linear-gradient(to bottom, black, transparent 85%);
            mask-image: linear-gradient(to bottom, black, transparent 85%);
        }
        .display { font-family: Oswald, Inter, sans-serif; letter-spacing:.01em; }
        .grad-text {
            background:linear-gradient(100deg,#c9a6ff,#8a36ff 45%,#ff2c78);
            -webkit-background-clip:text; background-clip:text; color:transparent;
        }
        .glow-btn { box-shadow:0 12px 42px rgba(128,43,255,.35); }
        .glow-btn:hover { box-shadow:0 18px 55px rgba(128,43,255,.55); }
        .card-glow:hover { border-color:rgba(155,94,255,.55); box-shadow:0 18px 60px rgba(82,16,176,.35); }

        /* Scroll reveal — starts hidden, released by IntersectionObserver. */
        .reveal { opacity:0; transform:translateY(22px); transition:opacity .6s ease, transform .6s ease; }
        .reveal.shown { opacity:1; transform:none; }

        /* Module detail. The 0fr→1fr grid trick collapses to zero here because the cards
           are h-full, which leaves the fr unit no free space to resolve against — so this
           animates max-height instead. Descriptions run ~100px; 420 leaves plenty of room
           without the easing feeling loose. */
        .mod-body { max-height:0; overflow:hidden; transition:max-height .32s ease; }
        .mod.open .mod-body { max-height:420px; }
        .mod.open { border-color:rgba(155,94,255,.6); background:rgba(255,255,255,.06); }
        .mod[hidden] { display:none; }

        #mobileNav { display:none; }
        #mobileNav.open { display:block; }

        @media (prefers-reduced-motion: reduce) {
            .reveal { opacity:1; transform:none; transition:none; }
            .mod-body { transition:none; }
        }
    </style>
</head>
<body class="antialiased text-[#f7f3ff]" style="font-family: Inter, system-ui, sans-serif;">
<div class="relative z-10">

    {{-- ───────────────────────── NAV ───────────────────────── --}}
    <header id="siteNav" class="fixed inset-x-0 top-0 z-50 border-b border-transparent transition-colors duration-300">
        <div class="mx-auto flex h-[72px] w-[min(1180px,calc(100%-32px))] items-center justify-between gap-6">
            <a href="#top" class="flex items-center gap-2.5">
                <x-brand-mark class="text-[22px]" />
            </a>

            <nav class="hidden items-center gap-7 text-sm text-[#b9aecb] lg:flex">
                <a href="#system" class="transition hover:text-white">The System</a>
                <a href="#roadmap" class="transition hover:text-white">Roadmap</a>
                <a href="#tools" class="transition hover:text-white">Tools</a>
                <a href="#enroll" class="transition hover:text-white">Enroll</a>
            </nav>

            <div class="flex items-center gap-2.5">
                @auth
                    <a href="{{ route('dashboard') }}"
                       class="rounded-xl bg-white/5 px-4 py-2.5 text-sm font-semibold ring-1 ring-white/15 transition hover:bg-white/10">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="hidden rounded-xl px-3 py-2.5 text-sm font-medium text-[#b9aecb] transition hover:text-white sm:block">
                        Log in
                    </a>
                @endauth
                <a href="{{ $fb }}" target="_blank" rel="noopener"
                   class="glow-btn rounded-xl bg-gradient-to-r from-[#7a2dff] via-[#9e3fff] to-[#ff2d7b] px-4 py-2.5 text-sm font-bold transition hover:-translate-y-0.5">
                    Enroll now
                </a>
                <button id="navToggle" type="button" class="p-2 text-[#b9aecb] lg:hidden"
                        aria-label="Menu" aria-expanded="false" aria-controls="mobileNav">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/>
                    </svg>
                </button>
            </div>
        </div>

        <div id="mobileNav" class="border-t border-white/10 bg-[#05030a]/95 backdrop-blur-xl lg:hidden">
            <nav class="mx-auto flex w-[min(1180px,calc(100%-32px))] flex-col py-3 text-sm">
                @foreach (['system' => 'The System', 'roadmap' => 'Roadmap', 'tools' => 'Tools', 'enroll' => 'Enroll'] as $id => $label)
                    <a href="#{{ $id }}" class="py-3 text-[#b9aecb] transition hover:text-white">{{ $label }}</a>
                @endforeach
                @guest
                    <a href="{{ route('login') }}" class="py-3 text-[#b9aecb] transition hover:text-white">Log in</a>
                @endguest
            </nav>
        </div>
    </header>

    {{-- ───────────────────────── HERO ───────────────────────── --}}
    <section id="top" class="relative flex min-h-[92vh] items-center px-4 pt-32 pb-20">
        <div class="mx-auto w-[min(1180px,100%)] text-center">
            <span class="reveal inline-flex items-center gap-2 rounded-full border border-[#9b5eff]/30 bg-[#8a36ff]/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-widest text-[#c9a6ff]">
                From product to profit
            </span>

            <h1 class="reveal display mt-7 text-[clamp(2.6rem,7.5vw,5.6rem)] font-bold uppercase leading-[0.95]">
                Build systems that<br>
                <span class="grad-text">print profit.</span>
            </h1>

            <p class="reveal mx-auto mt-7 max-w-2xl text-lg leading-relaxed text-[#b9aecb]">
                Not another course. A complete operating system for Filipino e-commerce sellers —
                strategy, automation, and the numbers behind every decision.
            </p>

            <div class="reveal mt-10 flex flex-wrap items-center justify-center gap-3">
                <a href="{{ $fb }}" target="_blank" rel="noopener"
                   class="glow-btn inline-flex items-center gap-2.5 rounded-xl bg-gradient-to-r from-[#7a2dff] via-[#9e3fff] to-[#ff2d7b] px-7 py-4 text-sm font-bold transition hover:-translate-y-0.5">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.68.24 2.68.24v2.96h-1.51c-1.49 0-1.96.93-1.96 1.89v2.26h3.33l-.53 3.49h-2.8V24C19.61 23.1 24 18.1 24 12.07z"/></svg>
                    Message us to enroll
                </a>
                <a href="#roadmap"
                   class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-7 py-4 text-sm font-bold transition hover:-translate-y-0.5 hover:bg-white/10">
                    See the 15 modules ↓
                </a>
            </div>

            <div class="reveal mx-auto mt-14 grid max-w-3xl grid-cols-3 gap-4">
                @foreach ([['15', 'Modules'], ['3', 'AI tools included'], ['1', 'Complete system']] as $stat)
                    <div class="rounded-2xl border border-white/10 bg-white/[.03] px-4 py-5">
                        <div class="display text-3xl font-bold text-white sm:text-4xl">{{ $stat[0] }}</div>
                        <div class="mt-1 text-[11px] uppercase tracking-widest text-[#8d7fa6]">{{ $stat[1] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ───────────────────────── THE SYSTEM ───────────────────────── --}}
    <section id="system" class="px-4 py-24">
        <div class="mx-auto w-[min(1180px,100%)]">
            <div class="reveal mx-auto max-w-3xl text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-[#8d7fa6]">The System</p>
                <h2 class="display mt-4 text-[clamp(2rem,4.5vw,3.2rem)] font-bold uppercase leading-tight">
                    Not another course.<br><span class="grad-text">A complete system.</span>
                </h2>
                <p class="mt-6 text-lg leading-relaxed text-[#b9aecb]">
                    Most training gives you tactics that stop working next quarter. Incepxion gives you the
                    structure underneath — how to find products, build creatives, read your own numbers,
                    and automate the parts that eat your day.
                </p>
            </div>

            <div class="mt-16 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($pillars as $i => $p)
                    <div class="reveal card-glow rounded-2xl border border-white/10 bg-white/[.03] p-7 transition duration-300 {{ $i === 3 ? 'lg:col-start-1' : '' }}">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-[#7a2dff] to-[#ff2d7b] text-2xl">{{ $p['icon'] }}</div>
                        <h3 class="display mt-5 text-xl font-semibold uppercase tracking-wide">{{ $p['title'] }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-[#b9aecb]">{{ $p['desc'] }}</p>
                    </div>
                @endforeach

                <div class="reveal flex flex-col justify-center rounded-2xl border border-[#9b5eff]/30 bg-gradient-to-br from-[#8a36ff]/15 to-[#ff2c78]/10 p-7">
                    <p class="display text-2xl font-bold uppercase leading-tight">Learn.<br>Apply.<br><span class="grad-text">Scale.</span></p>
                    <a href="#roadmap" class="mt-5 text-sm font-semibold text-[#c9a6ff] transition hover:text-white">Start with module 01 →</a>
                </div>
            </div>
        </div>
    </section>

    {{-- ───────────────────────── ROADMAP ───────────────────────── --}}
    <section id="roadmap" class="px-4 py-24">
        <div class="mx-auto w-[min(1180px,100%)]">
            <div class="reveal mx-auto max-w-3xl text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-[#8d7fa6]">Complete Training Roadmap</p>
                <h2 class="display mt-4 text-[clamp(2rem,4.5vw,3.2rem)] font-bold uppercase leading-tight">
                    15 modules.<br><span class="grad-text">One path.</span>
                </h2>
                <p class="mt-6 text-[#b9aecb]">Tap any module to see what's inside.</p>
            </div>

            {{-- Search: with 15 modules, letting people find their problem beats scrolling. --}}
            <div class="reveal mx-auto mt-9 max-w-md">
                <div class="relative">
                    <input type="text" id="modSearch" autocomplete="off"
                           placeholder="Search a topic — scaling, creatives, profit…"
                           class="w-full rounded-xl border border-white/10 bg-white/[.04] px-4 py-3 pr-10 text-sm text-white placeholder-[#7c6f95] focus:border-[#9b5eff]/60 focus:ring-0">
                    <button type="button" id="modSearchClear" hidden
                            class="absolute inset-y-0 right-3 text-lg text-[#8d7fa6] transition hover:text-white" aria-label="Clear search">&times;</button>
                </div>
            </div>

            <div id="modGrid" class="mt-12 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($modules as $m)
                    <button type="button"
                            class="mod reveal card-glow group h-full w-full rounded-2xl border border-white/10 bg-white/[.03] p-6 text-left transition duration-300"
                            data-search="{{ mb_strtolower($m['title'].' '.$m['tag'].' '.$m['desc']) }}"
                            aria-expanded="false">
                        <div class="flex items-start justify-between gap-3">
                            <span class="display rounded-lg bg-gradient-to-br from-[#7a2dff] to-[#ff2d7b] px-2.5 py-1 text-sm font-bold">{{ $m['n'] }}</span>
                            <span class="text-2xl opacity-80">{{ $m['icon'] }}</span>
                        </div>

                        <h3 class="display mt-4 text-lg font-semibold uppercase leading-snug tracking-wide">{{ $m['title'] }}</h3>
                        <p class="mt-1 text-xs font-semibold uppercase tracking-wider text-[#c9a6ff]">{{ $m['tag'] }}</p>

                        <div class="mod-body">
                            <div>
                                <p class="mt-4 border-t border-white/10 pt-4 text-sm leading-relaxed text-[#b9aecb]">{{ $m['desc'] }}</p>
                            </div>
                        </div>

                        <span class="mod-cue mt-4 inline-block text-xs font-semibold text-[#8d7fa6] transition group-hover:text-[#c9a6ff]">What's inside +</span>
                    </button>
                @endforeach
            </div>

            <p id="modEmpty" hidden class="mt-10 text-center text-[#8d7fa6]">
                No module matches that. Try &ldquo;scaling&rdquo;, &ldquo;creatives&rdquo;, or &ldquo;profit&rdquo;.
            </p>
        </div>
    </section>

    {{-- ───────────────────────── TOOLS ───────────────────────── --}}
    <section id="tools" class="px-4 py-24">
        <div class="mx-auto w-[min(1180px,100%)]">
            <div class="reveal mx-auto max-w-3xl text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-[#8d7fa6]">Included</p>
                <h2 class="display mt-4 text-[clamp(2rem,4.5vw,3.2rem)] font-bold uppercase leading-tight">
                    The tools that <span class="grad-text">run it.</span>
                </h2>
                <p class="mt-6 text-[#b9aecb]">Members get the software too — built for the exact workflow the modules teach.</p>
            </div>

            <div class="mt-14 grid gap-5 md:grid-cols-3">
                @foreach ([
                    ['📣', 'AI Ad Copy Generator', 'High-converting Facebook ad copy in Taglish, Filipino, or English — plus a ready-to-paste Botcake sales prompt.'],
                    ['📦', 'RTS Processor', 'Upload your J&T export to track Return-to-Sender rates, spot which products bleed, and project where the month is heading.'],
                    ['🧮', 'Profit Calculator', 'Real profit per order after COD fees, shipping, and returns — with target-profit analysis to hit the number you want.'],
                ] as $t)
                    <div class="reveal card-glow rounded-2xl border border-white/10 bg-white/[.03] p-7 transition duration-300">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-[#7a2dff] to-[#ff2d7b] text-2xl">{{ $t[0] }}</div>
                        <h3 class="display mt-5 text-lg font-semibold uppercase tracking-wide">{{ $t[1] }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-[#b9aecb]">{{ $t[2] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="reveal mt-10 text-center">
                @auth
                    <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-[#c9a6ff] transition hover:text-white">Open your dashboard →</a>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-semibold text-[#c9a6ff] transition hover:text-white">Already a member? Log in →</a>
                @endauth
            </div>
        </div>
    </section>

    {{-- ───────────────────────── ENROLL ───────────────────────── --}}
    <section id="enroll" class="px-4 py-24">
        <div class="mx-auto w-[min(1180px,100%)]">
            <div class="reveal relative overflow-hidden rounded-3xl border border-[#9b5eff]/30 bg-gradient-to-br from-[#8a36ff]/20 via-[#5f18d8]/10 to-[#ff2c78]/15 px-6 py-16 text-center sm:px-14">
                <h2 class="display text-[clamp(2rem,5vw,3.6rem)] font-bold uppercase leading-tight">
                    Ready to <span class="grad-text">build the system?</span>
                </h2>
                <p class="mx-auto mt-5 max-w-xl text-[#d6cbe8]">
                    Message us on Facebook to enroll and for payment details. We'll set up your access right away.
                </p>

                <a href="{{ $fb }}" target="_blank" rel="noopener"
                   class="glow-btn mt-9 inline-flex items-center gap-3 rounded-xl bg-gradient-to-r from-[#7a2dff] via-[#9e3fff] to-[#ff2d7b] px-8 py-4 font-bold transition hover:-translate-y-0.5">
                    <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.68.24 2.68.24v2.96h-1.51c-1.49 0-1.96.93-1.96 1.89v2.26h3.33l-.53 3.49h-2.8V24C19.61 23.1 24 18.1 24 12.07z"/></svg>
                    Message Nand Sam
                </a>

                <p class="mt-6 text-xs uppercase tracking-widest text-[#8d7fa6]">Enrollment &amp; payment via Facebook Messenger</p>
            </div>
        </div>
    </section>

    {{-- ───────────────────────── FOOTER ───────────────────────── --}}
    <footer class="border-t border-white/10 px-4 py-10">
        <div class="mx-auto flex w-[min(1180px,100%)] flex-col items-center justify-between gap-5 sm:flex-row">
            <x-brand-mark class="text-[19px]" sub="Services Inc." />

            <nav class="flex flex-wrap items-center justify-center gap-6 text-sm text-[#8d7fa6]">
                <a href="#system" class="transition hover:text-white">The System</a>
                <a href="#roadmap" class="transition hover:text-white">Roadmap</a>
                <a href="#tools" class="transition hover:text-white">Tools</a>
                <a href="{{ $fb }}" target="_blank" rel="noopener" class="transition hover:text-white">Facebook</a>
            </nav>

            <p class="text-xs text-[#6f6486]">© {{ date('Y') }} Incepxion Services Inc.</p>
        </div>
    </footer>
</div>

<script>
/*
 * Plain JS on purpose. Alpine reaches the rest of the app through Livewire's bundle, which
 * this standalone page doesn't load — and pulling Livewire in for a marketing page, or
 * adding a second Alpine alongside it, would cost more than these few listeners.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        /* Nav turns solid once you leave the hero. */
        var nav = document.getElementById('siteNav');
        var onScroll = function () {
            var solid = window.scrollY > 20;
            nav.classList.toggle('bg-[#05030a]/85', solid);
            nav.classList.toggle('backdrop-blur-xl', solid);
            nav.classList.toggle('border-white/10', solid);
            nav.classList.toggle('border-transparent', !solid);
        };
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();

        /* Mobile menu. */
        var toggle = document.getElementById('navToggle');
        var menu = document.getElementById('mobileNav');
        toggle.addEventListener('click', function () {
            var open = menu.classList.toggle('open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        menu.addEventListener('click', function (e) {
            if (e.target.tagName === 'A') {
                menu.classList.remove('open');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });

        /* Modules: one open at a time, so the grid never jumps around. */
        var mods = Array.prototype.slice.call(document.querySelectorAll('.mod'));
        mods.forEach(function (card) {
            card.addEventListener('click', function () {
                var wasOpen = card.classList.contains('open');
                mods.forEach(function (other) {
                    other.classList.remove('open');
                    other.setAttribute('aria-expanded', 'false');
                    other.querySelector('.mod-cue').textContent = "What's inside +";
                });
                if (!wasOpen) {
                    card.classList.add('open');
                    card.setAttribute('aria-expanded', 'true');
                    card.querySelector('.mod-cue').textContent = 'Hide −';
                }
            });
        });

        /* Search. */
        var search = document.getElementById('modSearch');
        var clear = document.getElementById('modSearchClear');
        var empty = document.getElementById('modEmpty');
        var filter = function () {
            var q = search.value.trim().toLowerCase();
            var shown = 0;
            mods.forEach(function (card) {
                var hit = q === '' || card.dataset.search.indexOf(q) !== -1;
                card.hidden = !hit;
                if (hit) shown++;
            });
            clear.hidden = q === '';
            empty.hidden = shown > 0;
        };
        search.addEventListener('input', filter);
        clear.addEventListener('click', function () {
            search.value = '';
            filter();
            search.focus();
        });

        /* Reveal on scroll. Without IntersectionObserver everything is simply shown. */
        var items = document.querySelectorAll('.reveal');
        if (!('IntersectionObserver' in window)) {
            items.forEach(function (el) { el.classList.add('shown'); });
            return;
        }
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (e.isIntersecting) {
                    e.target.classList.add('shown');
                    io.unobserve(e.target);
                }
            });
        }, { rootMargin: '0px 0px -12% 0px' });
        items.forEach(function (el, i) {
            el.style.transitionDelay = (Math.min(i % 6, 5) * 60) + 'ms';
            io.observe(el);
        });
    });
})();
</script>
{{-- Without JS the reveal animation never fires, so show everything rather than a blank page. --}}
<noscript><style>.reveal{opacity:1;transform:none}</style></noscript>
</body>
</html>
