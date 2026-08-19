<?php

use Livewire\Volt\Component;

new class extends Component {
    // Entered as PERCENT in the UI, stored as decimals in remit_fees.
    public $codFeePercent = null;
    public $codVatPercent = null;
    public bool $hasRates = false;
    public bool $editing = false;

    public function mount(): void
    {
        $this->loadFromUser();
        // First-time users (no rates yet) start in edit mode so they can enter.
        $this->editing = ! $this->hasRates;
    }

    private function loadFromUser(): void
    {
        $fees = auth()->user()->remitFees();
        $this->codFeePercent = $fees['cod_fee_rate'] !== null ? rtrim(rtrim(number_format($fees['cod_fee_rate'] * 100, 4, '.', ''), '0'), '.') : null;
        $this->codVatPercent = $fees['cod_fee_vat_rate'] !== null ? rtrim(rtrim(number_format($fees['cod_fee_vat_rate'] * 100, 4, '.', ''), '0'), '.') : null;
        $this->hasRates = $fees['cod_fee_rate'] !== null && $fees['cod_fee_vat_rate'] !== null;
    }

    public function save(): void
    {
        $this->validate([
            'codFeePercent' => ['required', 'numeric', 'min:0', 'max:100'],
            'codVatPercent' => ['required', 'numeric', 'min:0', 'max:100'],
        ], [], [
            'codFeePercent' => 'COD fee rate',
            'codVatPercent' => 'VAT rate',
        ]);

        $existing = auth()->user()->remitFees();

        auth()->user()->update(['remit_fees' => [
            'cod_fee_rate'           => round(((float) $this->codFeePercent) / 100, 6),
            'cod_fee_vat_rate'       => round(((float) $this->codVatPercent) / 100, 6),
            'shipping_fee_per_order' => $existing['shipping_fee_per_order'] ?? null,
        ]]);

        $this->loadFromUser();
        $this->editing = false;
        session()->flash('fee-status', 'saved');
    }

    public function startEdit(): void
    {
        $this->editing = true;
        $this->resetErrorBag();
    }

    public function cancelEdit(): void
    {
        $this->loadFromUser();
        $this->editing = false;
        $this->resetErrorBag();
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">{{ __('J&T Remittance Rates') }}</h2>
        <p class="mt-1 text-sm text-gray-600">
            {{ __('Used by the RTS Remittance tool. Shipping is taken from your actual data — only these two rates are needed.') }}
        </p>
    </header>

    @if ($hasRates && ! $editing)
        {{-- Read-only summary + Edit --}}
        <div class="mt-4 flex flex-wrap items-center gap-3 rounded-md bg-slate-50 border border-slate-200 px-4 py-3">
            <span class="text-sm text-gray-700">
                COD Fee: <strong>{{ $codFeePercent }}%</strong>
                <span class="mx-2 text-slate-300">|</span>
                VAT: <strong>{{ $codVatPercent }}%</strong>
            </span>
            <button wire:click="startEdit" class="ml-auto text-sm text-indigo-600 hover:underline">Edit</button>
        </div>

        @if (session('fee-status') === 'saved')
            <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)" class="mt-2 text-sm text-green-600">{{ __('Saved!') }}</p>
        @endif
    @else
        {{-- Edit form --}}
        <form wire:submit="save" class="mt-6 space-y-6">
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="codFeePercent" :value="__('COD Fee rate (%)')" />
                    <x-text-input wire:model="codFeePercent" id="codFeePercent" type="number" step="0.0001" min="0" max="100"
                                  class="mt-1 block w-full" placeholder="e.g. 2" />
                    <x-input-error class="mt-2" :messages="$errors->get('codFeePercent')" />
                </div>
                <div>
                    <x-input-label for="codVatPercent" :value="__('VAT rate (%) on COD fee')" />
                    <x-text-input wire:model="codVatPercent" id="codVatPercent" type="number" step="0.0001" min="0" max="100"
                                  class="mt-1 block w-full" placeholder="e.g. 12" />
                    <x-input-error class="mt-2" :messages="$errors->get('codVatPercent')" />
                </div>
            </div>

            <div class="flex items-center gap-4">
                <x-primary-button>{{ __('Save Rates') }}</x-primary-button>
                @if ($hasRates)
                    <button type="button" wire:click="cancelEdit" class="text-sm font-semibold text-gray-500 hover:text-gray-700">Cancel</button>
                @endif
            </div>
        </form>
    @endif
</section>
