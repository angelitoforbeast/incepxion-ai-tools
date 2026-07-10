<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <h1 class="text-xl font-bold text-slate-900 mb-1">Admin</h1>
    @include('partials.admin-nav')

    <div class="max-w-3xl">
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-slate-900">Ad Copy Generator — Prompt</h2>
            <p class="text-sm text-slate-500">Edit the system prompt that guides the AI. Changes apply to the next generation.</p>
        </div>

        @if (session('msg'))
            <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-2.5 text-sm text-emerald-700">
                ✓ {{ session('msg') }}
            </div>
        @endif

        <form wire:submit="save" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-5">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Default Model</label>
            <input type="text" wire:model="model"
                   class="w-full sm:w-64 rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
            <p class="mt-1 text-xs text-slate-400">e.g. gpt-4o, gpt-4o-mini, gpt-4-turbo</p>
            @error('model') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">System Prompt</label>
            <textarea wire:model="systemPrompt" rows="16"
                      class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm font-mono leading-relaxed"></textarea>
            @error('systemPrompt') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            <div class="mt-2 rounded-lg bg-slate-50 border border-slate-200 px-3 py-2 text-xs text-slate-500">
                💡 Use <code class="bg-slate-200 px-1 rounded">{language}</code> as a placeholder — it's replaced with the user's chosen language (Taglish / Filipino / English).
                The product details, tone, and number of variants are added automatically.
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                Save Prompt
            </button>
            <button type="button" wire:click="resetDefault" wire:confirm="Reset to the default prompt?"
                    class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                Reset to Default
            </button>
        </div>
        </form>

        <!-- History -->
        <div class="mt-8">
            <h3 class="text-sm font-semibold text-slate-900 mb-3">Version History</h3>
            @forelse ($versions as $v)
                <div class="flex items-start justify-between gap-4 rounded-xl border border-slate-200 bg-white px-4 py-3 mb-2">
                    <div class="min-w-0">
                        <div class="text-xs text-slate-500">
                            {{ $v->created_at->format('M d, Y g:i A') }}
                            · <span class="text-slate-600">{{ $v->author?->name ?? 'system' }}</span>
                            · <span class="font-mono">{{ $v->model }}</span>
                        </div>
                        <p class="mt-1 text-sm text-slate-600 line-clamp-2">{{ \Illuminate\Support\Str::limit($v->system_prompt, 160) }}</p>
                    </div>
                    <button wire:click="restore({{ $v->id }})"
                            class="flex-shrink-0 rounded-md border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-indigo-600 hover:bg-indigo-50">
                        Restore
                    </button>
                </div>
            @empty
                <p class="text-sm text-slate-400">No saved versions yet. Each time you Save, a version is added here.</p>
            @endforelse
        </div>
    </div>
</div>
