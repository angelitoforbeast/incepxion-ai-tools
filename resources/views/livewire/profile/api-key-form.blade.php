<?php

use App\Models\UserApiKey;
use Livewire\Volt\Component;

new class extends Component {
    public string $key = '';
    public ?string $masked = null;
    public bool $hasKey = false;

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
            'key.starts_with' => 'Ang OpenAI key ay dapat nagsisimula sa "sk-".',
        ]);

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
        $this->masked = $record->masked();

        session()->flash('apikey-status', 'saved');
    }

    public function remove(): void
    {
        UserApiKey::where('user_id', auth()->id())->where('provider', 'openai')->delete();

        $this->reset('key', 'masked');
        $this->hasKey = false;

        session()->flash('apikey-status', 'removed');
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('OpenAI API Key') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            {{ __('Ilagay ang sarili mong OpenAI API key. Naka-encrypt ito at ginagamit lang kapag nag-generate ka.') }}
        </p>
    </header>

    @if ($hasKey)
        <div class="mt-4 flex items-center gap-3 rounded-md bg-green-50 border border-green-200 px-4 py-3">
            <span class="text-green-600">🔑</span>
            <span class="text-sm text-gray-700">Naka-save na ang key mo: <strong>{{ $masked }}</strong></span>
            <button wire:click="remove" wire:confirm="Tanggalin ang naka-save na API key?"
                    class="ml-auto text-sm text-red-600 hover:underline">Remove</button>
        </div>
    @endif

    <form wire:submit="save" class="mt-6 space-y-6">
        <div>
            <x-input-label for="key" :value="$hasKey ? __('Palitan ang API Key') : __('OpenAI API Key')" />
            <x-text-input wire:model="key" id="key" name="key" type="password"
                          class="mt-1 block w-full" placeholder="sk-..." autocomplete="off" />
            <x-input-error class="mt-2" :messages="$errors->get('key')" />
            <p class="mt-1 text-xs text-gray-500">
                Kunin dito: <a href="https://platform.openai.com/api-keys" target="_blank" class="text-indigo-600 underline">platform.openai.com/api-keys</a>
            </p>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save Key') }}</x-primary-button>

            @if (session('apikey-status') === 'saved')
                <p x-data="{ show: true }" x-show="show" x-transition
                   x-init="setTimeout(() => show = false, 2500)" class="text-sm text-green-600">
                    {{ __('Na-save na!') }}
                </p>
            @elseif (session('apikey-status') === 'removed')
                <p x-data="{ show: true }" x-show="show" x-transition
                   x-init="setTimeout(() => show = false, 2500)" class="text-sm text-gray-600">
                    {{ __('Na-remove.') }}
                </p>
            @endif
        </div>
    </form>
</section>
