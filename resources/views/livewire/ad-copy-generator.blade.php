<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">📣 AI Ad Copy Generator</h1>
                <p class="text-sm text-gray-500">High-converting Facebook ad copy for the Filipino market.</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-sm font-medium text-indigo-700">
                    {{ $remaining }}/{{ $quota }} left today
                </span>
            </div>
        </div>

        @unless ($hasKey)
            <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                ⚠️ You have no OpenAI API key yet.
                <a href="{{ route('settings') }}" class="font-semibold underline">Add it in Settings</a>
                before generating.
            </div>
        @endunless

        <div class="grid grid-cols-1 lg:grid-cols-[380px_1fr] gap-6 items-start">

            <!-- Form -->
            <form wire:submit="generate" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <span class="text-xs font-medium text-gray-400">Your inputs</span>
                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="saveDefaults" class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-800">Save as default</button>
                        <span class="text-gray-300">·</span>
                        <button type="button" wire:click="resetDefaults" class="text-[11px] font-semibold text-gray-500 hover:text-gray-700">Reset</button>
                    </div>
                </div>
                @if (session('sp-msg'))
                    <p x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 2500)" class="text-xs text-emerald-600">✓ {{ session('sp-msg') }}</p>
                @endif
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Product / Service Name</label>
                    <input type="text" wire:model="product_name" placeholder="e.g. GlowUp Vitamin C Serum"
                           class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    @error('product_name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea wire:model="product_description" rows="4" placeholder="What is the product, benefits, price, offer..."
                              class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"></textarea>
                    @error('product_description') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Target Audience <span class="text-gray-400">(optional)</span></label>
                    <input type="text" wire:model="audience" placeholder="e.g. Moms 25-40, budget-conscious"
                           class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Language</label>
                        <select wire:model="language" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option>Taglish</option>
                            <option>Filipino</option>
                            <option>English</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Variants</label>
                        <select wire:model="variants" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            @for ($i = 1; $i <= 5; $i++)<option value="{{ $i }}">{{ $i }}</option>@endfor
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tone</label>
                    <input type="text" wire:model="tone" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                </div>

                <div x-data="{ c: @entangle('creativity') }">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Creativity <span class="text-gray-400">(<span x-text="parseFloat(c).toFixed(1)"></span>)</span>
                    </label>
                    <input type="range" wire:model="creativity" min="0" max="1" step="0.1" class="w-full accent-indigo-600">
                </div>

                <!-- Sales-prompt placeholder details (always visible) -->
                <div class="border-t border-gray-100 pt-4">
                    <div class="space-y-3">
                        @php
                            $spFields = ['STORE_NAME','PRODUCT_FEATURES','PRODUCT_PRICE','PROMO','PACKAGE_CONTENTS','PACKAGE_SUMMARY','UNIT_NAME','DELIVERY_TIME','PAYMENT_METHOD','LEGITIMACY_INFO','ORDER_FIELDS'];
                            $spMulti = ['PRODUCT_FEATURES','PROMO','PACKAGE_CONTENTS','LEGITIMACY_INFO','ORDER_FIELDS'];
                        @endphp
                        @foreach ($spFields as $k)
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-xs font-medium text-gray-600">{{ \App\Services\SalesPromptService::FIELDS[$k] }}</label>
                                    @if ($k === 'PRODUCT_FEATURES')
                                        <button type="button" wire:click="generateFeatures" wire:loading.attr="disabled" wire:target="generateFeatures"
                                                class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-800 disabled:opacity-50">
                                            <span wire:loading.remove wire:target="generateFeatures">✨ Generate with AI</span>
                                            <span wire:loading wire:target="generateFeatures">Generating…</span>
                                        </button>
                                    @endif
                                </div>
                                @if (in_array($k, $spMulti))
                                    <textarea wire:model="sp.{{ $k }}" rows="{{ $k === 'PRODUCT_FEATURES' ? 3 : 2 }}" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"></textarea>
                                @else
                                    <input type="text" wire:model="sp.{{ $k }}" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <button type="submit"
                        class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"
                        wire:loading.attr="disabled" wire:target="generate">
                    <span wire:loading.remove wire:target="generate">✨ Generate Ad Copy</span>
                    <span wire:loading wire:target="generate">Generating...</span>
                </button>
            </form>

            <!-- Results -->
            <div>
                @if ($error)
                    <div class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700">⚠️ {{ $error }}</div>
                @endif

                <div wire:loading wire:target="generate" class="space-y-4">
                    @for ($i = 0; $i < $variants; $i++)
                        <div class="h-40 rounded-xl bg-gray-200 animate-pulse"></div>
                    @endfor
                </div>

                <div wire:loading.remove wire:target="generate" class="space-y-4">
                    @forelse ($results as $i => $v)
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5" x-data>
                            <div class="flex items-center justify-between mb-3">
                                <strong class="text-gray-900">Variant {{ $i + 1 }}</strong>
                                <span class="text-xs font-semibold uppercase tracking-wide text-teal-600 bg-teal-50 rounded-full px-3 py-1">{{ $v['angle'] ?? 'Ad' }}</span>
                            </div>

                            @foreach (['Headline' => 'headline', 'Primary Text' => 'primary_text', 'Messaging Template' => 'messaging_template'] as $label => $key)
                                <div class="mb-3">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs uppercase tracking-wide text-gray-400">{{ $label }}</span>
                                        <button type="button" class="text-xs font-semibold text-indigo-500 hover:text-indigo-700"
                                                @click="navigator.clipboard.writeText($refs.f{{ $i }}{{ $loop->index }}.innerText); $wire.recordCopy({{ $i }}, '{{ $key }}'); $el.innerText='✓ Copied!'; setTimeout(() => $el.innerText='Copy', 1400)">Copy</button>
                                    </div>
                                    <div x-ref="f{{ $i }}{{ $loop->index }}"
                                         class="select-none rounded-lg bg-gray-50 border border-gray-200 px-3 py-2 text-sm text-gray-800 whitespace-pre-wrap {{ $key === 'headline' ? 'font-semibold' : '' }}">{{ $v[$key] ?? '' }}</div>
                                </div>
                            @endforeach

                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs uppercase tracking-wide text-gray-400">Quick Replies</span>
                                    <span class="text-[10px] text-gray-300">tap to copy</span>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    @foreach (($v['quick_replies'] ?? []) as $qi => $qr)
                                        <button type="button" x-data
                                                class="select-none inline-flex items-center gap-1.5 rounded-full bg-gray-100 border border-gray-200 px-3 py-1 text-sm text-gray-700 hover:border-indigo-300 hover:bg-indigo-50 transition"
                                                @click="navigator.clipboard.writeText(@js($qr)); $wire.recordCopy({{ $i }}, 'quick_reply_{{ $qi }}'); $el.querySelector('.qrlabel').innerText='✓ Copied!'; setTimeout(() => $el.querySelector('.qrlabel').innerText=@js($qr), 1200)">
                                            <span class="qrlabel">{{ $qr }}</span>
                                            <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @empty
                        @unless ($error)
                            <div class="flex flex-col items-center justify-center min-h-[340px] rounded-xl border-2 border-dashed border-gray-200 text-gray-400 text-center p-10">
                                <div class="text-4xl mb-3">📝</div>
                                <p>Fill in the form on the left and click <strong>Generate</strong>.<br>Your ad copy variants will appear here.</p>
                            </div>
                        @endunless
                    @endforelse

                    @if ($generatedPrompt)
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5" x-data>
                            <div class="flex items-center justify-between mb-2">
                                <strong class="text-gray-900">🤖 BotCake Sales Prompt</strong>
                                <button type="button" class="text-xs font-semibold text-indigo-500 hover:text-indigo-700"
                                        @click="navigator.clipboard.writeText($refs.sp.innerText); $wire.recordCopy(-1, 'sales_prompt'); $el.innerText='✓ Copied!'; setTimeout(() => $el.innerText='Copy', 1400)">Copy</button>
                            </div>
                            <div x-ref="sp" class="select-none rounded-lg bg-gray-50 border border-gray-200 px-3 py-2 text-xs text-gray-800 whitespace-pre-wrap max-h-96 overflow-y-auto font-mono leading-relaxed">{{ $generatedPrompt }}</div>
                            <p class="mt-2 text-xs text-gray-400">Ready to paste into BotCake AI.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
