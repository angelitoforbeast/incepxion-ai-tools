<div class="lg:h-screen lg:flex lg:flex-col lg:overflow-hidden">
    <!-- Header -->
    <div class="flex-shrink-0 bg-slate-50 border-b border-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">📣 AI Ad Copy Generator</h1>
                <p class="text-sm text-gray-500">High-converting Facebook ad copy for the Filipino market.</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-sm font-medium text-indigo-700">
                {{ $remaining }}/{{ $quota }} left today
            </span>
        </div>
    </div>

    <div class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:flex-1 lg:min-h-0 lg:flex lg:flex-col">

        @unless ($hasKey)
            <div class="mb-4 flex-shrink-0 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                ⚠️ You have no OpenAI API key yet.
                <a href="{{ route('settings') }}" class="font-semibold underline">Add it in Settings</a>
                before generating.
            </div>
        @endunless

        <div class="flex flex-col lg:flex-row gap-6 lg:flex-1 lg:min-h-0">

            <!-- Form -->
            <form wire:submit="generate" class="lg:w-[480px] lg:flex-shrink-0 lg:flex lg:flex-col lg:min-h-0 min-w-0 bg-white rounded-xl shadow-sm border border-gray-200">
                <!-- Fixed action bar (does not scroll) -->
                <div class="flex-shrink-0 p-4 border-b border-gray-100 space-y-2">
                    <div class="flex items-center gap-2">
                        <button type="submit"
                                class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"
                                wire:loading.attr="disabled" wire:target="generate">
                            <svg wire:loading wire:target="generate" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="generate">✨ Generate Ad Copy</span>
                            <span wire:loading wire:target="generate">Generating...</span>
                        </button>
                        <button type="button" wire:click="fillDemo" wire:loading.attr="disabled" wire:target="generate"
                                class="rounded-lg border border-gray-200 px-3 py-2.5 text-xs font-semibold text-emerald-600 hover:bg-emerald-50 whitespace-nowrap">Fill demo</button>
                        <button type="button" wire:click="saveDefaults" wire:loading.attr="disabled" wire:target="generate"
                                class="rounded-lg border border-gray-200 px-3 py-2.5 text-xs font-semibold text-indigo-600 hover:bg-indigo-50 whitespace-nowrap">Save default</button>
                        <button type="button" wire:click="resetDefaults" wire:loading.attr="disabled" wire:target="generate"
                                class="rounded-lg border border-gray-200 px-3 py-2.5 text-xs font-semibold text-gray-500 hover:bg-gray-50">Reset</button>
                    </div>
                    @if (session('sp-msg'))
                        <p x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 2500)" class="text-xs text-emerald-600">✓ {{ session('sp-msg') }}</p>
                    @endif
                </div>

                <!-- Scrollable inputs -->
                <div class="lg:flex-1 lg:overflow-y-auto lg:min-h-0 p-6 space-y-4">

                <div class="rounded-lg border border-dashed border-gray-300 p-3">
                    <label class="block text-xs font-medium text-gray-500 mb-2">📷 Upload product image <span class="text-gray-400">(optional — para sa auto-fill)</span></label>
                    <input type="file" wire:model="uploadedImage" accept="image/*"
                           class="block w-full text-xs text-gray-600 file:mr-2 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-indigo-600 hover:file:bg-indigo-100">
                    @error('uploadedImage') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    <div wire:loading wire:target="uploadedImage" class="mt-2 text-xs text-gray-400">Uploading…</div>

                    @if ($uploadedImage)
                        <div class="mt-3">
                            <img src="{{ $uploadedImage->temporaryUrl() }}" alt="Uploaded product" class="rounded-lg border border-gray-200 w-full max-w-[200px]">
                            <div class="mt-2 flex items-center gap-3">
                                <button type="button" wire:click="autoFillFromImage" wire:loading.attr="disabled" wire:target="autoFillFromImage"
                                        class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700 disabled:opacity-60">
                                    <span wire:loading.remove wire:target="autoFillFromImage">✨ Auto-fill from image</span>
                                    <span wire:loading wire:target="autoFillFromImage">Analyzing…</span>
                                </button>
                                <button type="button" wire:click="removeUpload" class="text-xs text-gray-400 hover:text-gray-600">Remove</button>
                            </div>
                        </div>
                    @endif
                </div>

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
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-medium text-gray-700">Key Features <span class="ml-1 text-[10px] bg-indigo-100 text-indigo-700 rounded px-1.5 py-0.5">AI</span></label>
                        <button type="button" wire:click="generateFeatures" wire:loading.attr="disabled" wire:target="generateFeatures"
                                class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-800 disabled:opacity-50">
                            <span wire:loading.remove wire:target="generateFeatures">✨ Generate with AI</span>
                            <span wire:loading wire:target="generateFeatures">Generating…</span>
                        </button>
                    </div>
                    <textarea wire:model="sp.PRODUCT_FEATURES" rows="3" placeholder="✅ Feature 1&#10;✅ Feature 2&#10;✅ Feature 3"
                              class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"></textarea>
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
                            <option value="1">1</option>
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
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @php
                            $spFields = ['STORE_NAME','PRODUCT_PRICE','PROMO','DELIVERY_TIME','PAYMENT_METHOD','LEGITIMACY_INFO','ORDER_FIELDS'];
                            $spMulti = ['PROMO','LEGITIMACY_INFO','ORDER_FIELDS'];
                            $spFull = ['LEGITIMACY_INFO','ORDER_FIELDS'];
                        @endphp
                        @foreach ($spFields as $k)
                            <div class="{{ in_array($k, $spFull) ? 'sm:col-span-2' : '' }}">
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-xs font-medium text-gray-600">{{ \App\Services\SalesPromptService::FIELDS[$k] }}@if (in_array($k, \App\Services\SalesPromptService::REQUIRED))<span class="text-red-500"> *</span>@endif</label>
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
                                @error('sp.'.$k) <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>
                        @endforeach
                    </div>
                </div>
                </div>

            </form>

            <!-- Results -->
            <div class="min-w-0 lg:flex-1 lg:overflow-y-auto lg:min-h-0">
                @if ($error)
                    <div class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700">⚠️ {{ $error }}</div>
                @endif

                <div wire:loading wire:target="generate" class="space-y-4">
                    <div class="flex flex-col items-center justify-center rounded-xl border border-indigo-100 bg-indigo-50/60 py-8 text-center">
                        <svg class="animate-spin w-9 h-9 text-indigo-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <p class="mt-3 text-sm font-semibold text-indigo-700">Generating your ad copy…</p>
                        <p class="text-xs text-indigo-400">The AI is writing {{ $variants }} variant{{ $variants > 1 ? 's' : '' }} + sales prompt. This takes a few seconds.</p>
                    </div>
                    @for ($i = 0; $i < $variants; $i++)
                        <div class="h-40 rounded-xl bg-gray-200 animate-pulse"></div>
                    @endfor
                </div>

                <div wire:loading.remove wire:target="generate" class="space-y-4">
                    @if ($mainFlow)
                        <div class="bg-white rounded-xl shadow-sm border-2 border-indigo-200 p-5" x-data>
                            <div class="flex items-center justify-between mb-2">
                                <strong class="text-indigo-700">📢 Main Flow — first auto-reply</strong>
                                <div class="flex items-center gap-3">
                                    <button type="button" wire:click="regenerateMainFlow" wire:loading.attr="disabled" wire:target="regenerateMainFlow"
                                            title="Regenerate main flow" class="text-gray-400 hover:text-indigo-600">
                                        <svg wire:loading.remove wire:target="regenerateMainFlow" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        <svg wire:loading wire:target="regenerateMainFlow" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                                    </button>
                                    <button type="button" class="text-xs font-semibold text-indigo-500 hover:text-indigo-700"
                                            @click="navigator.clipboard.writeText($refs.mf.innerText); $wire.recordCopy(-1, 'main_flow'); $el.innerText='✓ Copied!'; setTimeout(() => $el.innerText='Copy', 1400)">Copy</button>
                                </div>
                            </div>
                            <div x-ref="mf" class="select-none rounded-lg bg-indigo-50 border border-indigo-100 px-3 py-2.5 text-sm text-gray-800 whitespace-pre-wrap break-words">{{ $mainFlow }}</div>
                            <p class="mt-2 text-xs text-gray-400">The bot sends this as its first reply to any chat.</p>

                            <div class="mt-3 border-t border-gray-100 pt-3" @if ($imageGenerating) wire:poll.2s="checkImage" @endif>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <button type="button" wire:click="generateImage" @if ($imageGenerating) disabled @endif
                                            class="inline-flex items-center gap-2 rounded-lg bg-violet-600 px-3 py-2 text-xs font-semibold text-white hover:bg-violet-700 disabled:opacity-60">
                                        @if ($imageGenerating)
                                            <svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                                            Generating…
                                        @else
                                            🖼️ Generate promo image
                                        @endif
                                    </button>
                                    <label class="text-xs text-gray-500">Size:</label>
                                    <select wire:model="imageSize" @if ($imageGenerating) disabled @endif class="rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500 py-1.5">
                                        <option value="480">480 × 480</option>
                                        <option value="640">640 × 640</option>
                                        <option value="800">800 × 800</option>
                                        <option value="1024">1024 × 1024 (HD)</option>
                                    </select>
                                </div>
                                @if ($promoImageUrl)
                                    <div class="mt-3" x-data="{ zoom: false }">
                                        <img src="{{ $promoImageUrl }}" alt="Promo image" @click="zoom = true"
                                             class="cursor-zoom-in rounded-lg border border-gray-200 w-full max-w-[240px] hover:opacity-90 transition">
                                        <a href="{{ $promoImageUrl }}" download target="_blank" class="mt-1 inline-block text-xs font-semibold text-indigo-600 hover:underline">⬇️ Download image</a>
                                        <div x-show="zoom" x-transition @click="zoom = false" style="display:none"
                                             class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80 p-4 cursor-zoom-out">
                                            <img src="{{ $promoImageUrl }}" alt="Promo image (zoomed)" class="max-h-full max-w-full rounded-lg shadow-2xl">
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                    @forelse ($results as $i => $v)
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5" x-data>
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <strong class="text-gray-900">Ad Creatives{{ count($results) > 1 ? ' '.($i + 1) : '' }}</strong>
                                    @if ($i === 0)
                                        <button type="button" wire:click="regenerateAdCreatives" wire:loading.attr="disabled" wire:target="regenerateAdCreatives"
                                                title="Regenerate ad creatives" class="text-gray-400 hover:text-indigo-600">
                                            <svg wire:loading.remove wire:target="regenerateAdCreatives" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            <svg wire:loading wire:target="regenerateAdCreatives" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                                        </button>
                                    @endif
                                </div>
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
                            <p class="mt-2 text-xs text-gray-400">For sales. Ready to paste into BotCake AI.</p>

                            <div class="mt-3 border-t border-gray-100 pt-3">
                                <label class="text-xs font-semibold text-gray-500">🧪 Test this prompt — type a customer message:</label>
                                <div class="flex gap-2 mt-1">
                                    <input type="text" wire:model="salesTestInput" wire:keydown.enter.prevent="testSales" placeholder="e.g. Magkano po? Legit ba kayo?"
                                           class="flex-1 rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <button type="button" wire:click="testSales" wire:loading.attr="disabled" wire:target="testSales"
                                            class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700 disabled:opacity-60">
                                        <span wire:loading.remove wire:target="testSales">Send</span>
                                        <span wire:loading wire:target="testSales">…</span>
                                    </button>
                                </div>
                                <div wire:loading wire:target="testSales" class="mt-2 text-xs text-gray-400">AI is replying…</div>
                                @if ($salesTestReply)
                                    <div wire:loading.remove wire:target="testSales" class="mt-2">
                                        <span class="text-[10px] uppercase tracking-wide text-indigo-400 block mb-1">Assistant reply</span>
                                        <div class="inline-block max-w-full rounded-2xl rounded-tl-sm bg-indigo-100 px-4 py-2.5 text-sm text-gray-800 whitespace-pre-wrap break-words">{{ $salesTestReply }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if ($generatedAfterSalesPrompt)
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5" x-data>
                            <div class="flex items-center justify-between mb-2">
                                <strong class="text-gray-900">🤝 BotCake After-Sales Prompt</strong>
                                <button type="button" class="text-xs font-semibold text-indigo-500 hover:text-indigo-700"
                                        @click="navigator.clipboard.writeText($refs.asp.innerText); $wire.recordCopy(-1, 'aftersales_prompt'); $el.innerText='✓ Copied!'; setTimeout(() => $el.innerText='Copy', 1400)">Copy</button>
                            </div>
                            <div x-ref="asp" class="select-none rounded-lg bg-gray-50 border border-gray-200 px-3 py-2 text-xs text-gray-800 whitespace-pre-wrap max-h-96 overflow-y-auto font-mono leading-relaxed">{{ $generatedAfterSalesPrompt }}</div>
                            <p class="mt-2 text-xs text-gray-400">For after-sales support. Ready to paste into BotCake AI.</p>

                            <div class="mt-3 border-t border-gray-100 pt-3">
                                <label class="text-xs font-semibold text-gray-500">🧪 Test this prompt — type a customer message:</label>
                                <div class="flex gap-2 mt-1">
                                    <input type="text" wire:model="afterSalesTestInput" wire:keydown.enter.prevent="testAfterSales" placeholder="e.g. Nasaan na po order ko? Pwede po bang buksan?"
                                           class="flex-1 rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <button type="button" wire:click="testAfterSales" wire:loading.attr="disabled" wire:target="testAfterSales"
                                            class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700 disabled:opacity-60">
                                        <span wire:loading.remove wire:target="testAfterSales">Send</span>
                                        <span wire:loading wire:target="testAfterSales">…</span>
                                    </button>
                                </div>
                                <div wire:loading wire:target="testAfterSales" class="mt-2 text-xs text-gray-400">AI is replying…</div>
                                @if ($afterSalesTestReply)
                                    <div wire:loading.remove wire:target="testAfterSales" class="mt-2">
                                        <span class="text-[10px] uppercase tracking-wide text-indigo-400 block mb-1">Assistant reply</span>
                                        <div class="inline-block max-w-full rounded-2xl rounded-tl-sm bg-indigo-100 px-4 py-2.5 text-sm text-gray-800 whitespace-pre-wrap break-words">{{ $afterSalesTestReply }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Follow-up SEQUENCE (BotCake broadcast messages) --}}
                    @if ($generatedPrompt)
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5" x-data>
                            <div class="flex items-center justify-between mb-1 flex-wrap gap-2">
                                <strong class="text-gray-900">🔁 Follow-up Sequence <span class="text-xs font-normal text-gray-400">(BotCake broadcast)</span></strong>
                                <div class="flex items-center gap-2">
                                    <label class="text-xs text-gray-500">Messages:</label>
                                    <select wire:model="sequenceCount" @disabled($generatedPrompt === null) wire:loading.attr="disabled" wire:target="generateSequence"
                                            class="rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500 py-1.5">
                                        @for ($n = 1; $n <= 20; $n++)<option value="{{ $n }}">{{ $n }}</option>@endfor
                                    </select>
                                    <button type="button" wire:click="generateSequence" wire:loading.attr="disabled" wire:target="generateSequence"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700 disabled:opacity-60">
                                        <span wire:loading.remove wire:target="generateSequence">Generate sequence</span>
                                        <span wire:loading wire:target="generateSequence" class="inline-flex items-center gap-1.5">
                                            <svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                                            Generating…
                                        </span>
                                    </button>
                                </div>
                            </div>
                            <p class="text-xs text-gray-400 mb-3">Follow-up messages to re-engage customers who haven't ordered yet. Uses <code class="bg-gray-100 px-1 rounded">@{{first_name}}</code>, <code class="bg-gray-100 px-1 rounded">@{{PRICING}}</code>, <code class="bg-gray-100 px-1 rounded">@{{FFORM}}</code> placeholders.</p>

                            <div wire:loading wire:target="generateSequence" class="text-xs text-gray-400">Writing {{ $sequenceCount }} follow-ups…</div>

                            @if (count($sequenceMessages))
                                <div wire:loading.remove wire:target="generateSequence" class="space-y-2" x-data>
                                    <div class="flex justify-end">
                                        <button type="button" class="text-xs font-semibold text-indigo-500 hover:text-indigo-700"
                                                @click="navigator.clipboard.writeText($refs.seqall.innerText); $el.innerText='✓ Copied all!'; setTimeout(() => $el.innerText='Copy all', 1400)">Copy all</button>
                                    </div>
                                    <div x-ref="seqall" class="hidden">{{ implode("\n\n-\n\n", $sequenceMessages) }}</div>
                                    @foreach ($sequenceMessages as $si => $msg)
                                        <div class="rounded-lg bg-gray-50 border border-gray-200 p-3" x-data>
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="text-[10px] uppercase tracking-wide text-indigo-400">Sequence {{ $si + 1 }}</span>
                                                <button type="button" class="text-xs font-semibold text-indigo-500 hover:text-indigo-700"
                                                        @click="navigator.clipboard.writeText($refs.seq{{ $si }}.innerText); $el.innerText='✓'; setTimeout(() => $el.innerText='Copy', 1200)">Copy</button>
                                            </div>
                                            <div x-ref="seq{{ $si }}" class="select-none whitespace-pre-wrap text-sm text-gray-800 leading-relaxed">{{ $msg }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
