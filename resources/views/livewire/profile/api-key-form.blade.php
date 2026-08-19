<?php

use App\Models\UserApiKey;
use Livewire\Volt\Component;

new class extends Component {
    public string $key = '';
    public ?string $masked = null;
    public bool $hasKey = false;
    public bool $replacing = false;

    public function mount(): void
    {
        if ($existing = auth()->user()->apiKeyFor('openai')) {
            $this->hasKey = true;
            $this->masked = $existing->masked();
        }
    }

    public function save(): void
    {
        $this->validate([
            'key' => ['required', 'string', 'min:20', 'starts_with:sk-'],
        ], [
            'key.starts_with' => 'The OpenAI key must start with "sk-".',
        ]);

        // One key per user (updates the existing one instead of adding another).
        $record = UserApiKey::firstOrNew([
            'user_id'  => auth()->id(),
            'provider' => 'openai',
        ]);
        $record->setKey(trim($this->key));
        $record->label = 'OpenAI';
        $record->is_valid = true;
        $record->save();

        $this->reset('key');
        $this->hasKey = true;
        $this->replacing = false;
        $this->masked = $record->masked();

        session()->flash('apikey-status', 'saved');
    }

    public function remove(): void
    {
        UserApiKey::where('user_id', auth()->id())->where('provider', 'openai')->delete();

        $this->reset('key', 'masked');
        $this->hasKey = false;
        $this->replacing = false;

        session()->flash('apikey-status', 'removed');
    }

    public function startReplace(): void
    {
        $this->replacing = true;
        $this->reset('key');
        $this->resetErrorBag();
    }

    public function cancelReplace(): void
    {
        $this->replacing = false;
        $this->reset('key');
        $this->resetErrorBag();
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('OpenAI API Key') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            {{ __('One OpenAI key per account. It is encrypted and only used when you generate.') }}
        </p>
    </header>

    @if ($hasKey && ! $replacing)
        {{-- A key is saved: show it, with Replace / Remove. No second key can be added. --}}
        <div class="mt-4 flex flex-wrap items-center gap-3 rounded-md bg-green-50 border border-green-200 px-4 py-3">
            <span class="text-green-600">🔑</span>
            <span class="text-sm text-gray-700">Your key is saved: <strong>{{ $masked }}</strong></span>
            <div class="ml-auto flex items-center gap-3">
                <button wire:click="startReplace" class="text-sm text-indigo-600 hover:underline">Replace</button>
                <button wire:click="remove" wire:confirm="Remove the saved API key?" class="text-sm text-red-600 hover:underline">Remove</button>
            </div>
        </div>

        @if (session('apikey-status') === 'saved')
            <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)" class="mt-2 text-sm text-green-600">{{ __('Saved!') }}</p>
        @endif
    @else
        {{-- No key yet, or replacing the existing one. --}}
        <form wire:submit="save" class="mt-6 space-y-6">
            <div>
                <x-input-label for="key" :value="$hasKey ? __('Replace API Key') : __('OpenAI API Key')" />
                <x-text-input wire:model="key" id="key" name="key" type="password"
                              class="mt-1 block w-full" placeholder="sk-..." autocomplete="off" />
                <x-input-error class="mt-2" :messages="$errors->get('key')" />
                <p class="mt-1 text-xs text-gray-500">
                    Get one at <a href="https://platform.openai.com/api-keys" target="_blank" class="text-indigo-600 underline">platform.openai.com/api-keys</a>
                </p>
            </div>

            <div class="flex items-center gap-4">
                <x-primary-button>{{ $hasKey ? __('Update Key') : __('Save Key') }}</x-primary-button>

                @if ($hasKey)
                    <button type="button" wire:click="cancelReplace" class="text-sm font-semibold text-gray-500 hover:text-gray-700">Cancel</button>
                @endif

                @if (session('apikey-status') === 'removed')
                    <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)" class="text-sm text-gray-600">{{ __('Removed.') }}</p>
                @endif
            </div>
        </form>
    @endif
</section>
