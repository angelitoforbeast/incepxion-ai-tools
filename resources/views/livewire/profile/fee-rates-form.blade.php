<?php

use Livewire\Volt\Component;

new class extends Component {
    // Entered as PERCENT in the UI, stored as decimals in remit_fees.
    public $codFeePercent = null;
    public $codVatPercent = null;

    public function mount(): void
    {
        $fees = auth()->user()->remitFees();
        $this->codFeePercent = $fees['cod_fee_rate'] !== null ? rtrim(rtrim(number_format($fees['cod_fee_rate'] * 100, 4, '.', ''), '0'), '.') : null;
        $this->codVatPercent = $fees['cod_fee_vat_rate'] !== null ? rtrim(rtrim(number_format($fees['cod_fee_vat_rate'] * 100, 4, '.', ''), '0'), '.') : null;
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
            // Preserve any legacy value; the anomaly feature no longer uses it.
            'shipping_fee_per_order' => $existing['shipping_fee_per_order'] ?? null,
        ]]);

        session()->flash('fee-status', 'saved');
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">{{ __('J&T Remittance Rates') }}</h2>
        <p class="mt-1 text-sm text-gray-600">
            {{ __('Used by the RTS Remittance tool. Shipping is taken from your actual data — only these two rates are needed.') }}
        </p>
    </header>

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

            @if (session('fee-status') === 'saved')
                <p x-data="{ show: true }" x-show="show" x-transition
                   x-init="setTimeout(() => show = false, 2500)" class="text-sm text-green-600">{{ __('Saved!') }}</p>
            @endif
        </div>
    </form>
</section>
