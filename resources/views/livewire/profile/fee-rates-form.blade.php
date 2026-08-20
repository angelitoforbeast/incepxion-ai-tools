<?php

use App\Models\UserFeeRate;
use Livewire\Volt\Component;

new class extends Component {
    public string $newDate = '';
    public $newCod = null;
    public $newVat = null;

    /** The add form stays hidden behind a button until it's actually wanted. */
    public bool $adding = false;

    public function startAdd(): void
    {
        $this->adding = true;
    }

    public function cancelAdd(): void
    {
        $this->reset('newDate', 'newCod', 'newVat', 'adding');
        $this->resetErrorBag();
    }

    public function addRate(): void
    {
        $this->validate([
            'newDate' => ['required', 'date'],
            'newCod'  => ['required', 'numeric', 'min:0', 'max:100'],
            'newVat'  => ['required', 'numeric', 'min:0', 'max:100'],
        ], [], [
            'newDate' => 'effective date',
            'newCod'  => 'COD fee rate',
            'newVat'  => 'VAT rate',
        ]);

        // One row per effective date — re-adding the same date updates it.
        UserFeeRate::updateOrCreate(
            ['user_id' => auth()->id(), 'effective_date' => $this->newDate],
            [
                'cod_fee_rate'     => round(((float) $this->newCod) / 100, 6),
                'cod_fee_vat_rate' => round(((float) $this->newVat) / 100, 6),
            ],
        );

        $this->reset('newDate', 'newCod', 'newVat', 'adding');
        session()->flash('fee-status', 'saved');
    }

    public function deleteRate(int $id): void
    {
        UserFeeRate::where('user_id', auth()->id())->whereKey($id)->delete();
    }

    public function with(): array
    {
        return [
            'rates' => auth()->user()->feeRates()->orderByDesc('effective_date')->get(),
        ];
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">{{ __('J&T Remittance Rates') }}</h2>
        <p class="mt-1 text-sm text-gray-600">
            {{ __('Used by the RTS Remittance tool. Set a rate with the date it took effect — when J&T changes a rate, add a new row and remittance uses the right rate per date.') }}
        </p>
    </header>

    {{-- Existing dated rates --}}
    <div class="mt-4 overflow-x-auto">
        <table class="min-w-full text-sm border border-gray-200">
            <thead class="bg-gray-50 text-gray-600">
                <tr class="text-left">
                    <th class="px-3 py-2 font-medium">Effective from</th>
                    <th class="px-3 py-2 font-medium text-right">COD Fee %</th>
                    <th class="px-3 py-2 font-medium text-right">VAT %</th>
                    <th class="px-3 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($rates as $rate)
                    <tr>
                        <td class="px-3 py-2 whitespace-nowrap">{{ $rate->effective_date->format('M d, Y') }}</td>
                        <td class="px-3 py-2 text-right">{{ rtrim(rtrim(number_format($rate->cod_fee_rate * 100, 4), '0'), '.') }}%</td>
                        <td class="px-3 py-2 text-right">{{ rtrim(rtrim(number_format($rate->cod_fee_vat_rate * 100, 4), '0'), '.') }}%</td>
                        <td class="px-3 py-2 text-right">
                            <button wire:click="deleteRate({{ $rate->id }})" wire:confirm="Remove this rate?" class="text-xs text-red-600 hover:underline">Remove</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-3 py-4 text-center text-gray-400">No rates yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if (session('fee-status') === 'saved')
        <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)" class="mt-2 text-sm text-green-600">{{ __('Saved!') }}</p>
    @endif

    {{-- Add a new dated rate — revealed on demand, like the API key form. --}}
    @if (! $adding)
        <div class="mt-4">
            <x-primary-button type="button" wire:click="startAdd">{{ __('Add rate') }}</x-primary-button>
        </div>
    @else
    <form wire:submit="addRate" class="mt-6 border-t border-gray-100 pt-4">
        <p class="text-sm font-semibold text-gray-700 mb-2">Add a rate</p>
        <div class="grid sm:grid-cols-3 gap-3">
            <div>
                <x-input-label for="newDate" :value="__('Effective from')" />
                <x-text-input wire:model="newDate" id="newDate" type="date" class="mt-1 block w-full" />
                <x-input-error class="mt-2" :messages="$errors->get('newDate')" />
            </div>
            <div>
                <x-input-label for="newCod" :value="__('COD Fee rate (%)')" />
                <x-text-input wire:model="newCod" id="newCod" type="number" step="0.0001" min="0" max="100" class="mt-1 block w-full" placeholder="e.g. 2" />
                <x-input-error class="mt-2" :messages="$errors->get('newCod')" />
            </div>
            <div>
                <x-input-label for="newVat" :value="__('VAT rate (%)')" />
                <x-text-input wire:model="newVat" id="newVat" type="number" step="0.0001" min="0" max="100" class="mt-1 block w-full" placeholder="e.g. 12" />
                <x-input-error class="mt-2" :messages="$errors->get('newVat')" />
            </div>
        </div>
        <div class="mt-3 flex items-center gap-4">
            <x-primary-button>{{ __('Save rate') }}</x-primary-button>
            <button type="button" wire:click="cancelAdd" class="text-sm font-semibold text-gray-500 hover:text-gray-700">Cancel</button>
        </div>
    </form>
    @endif
</section>
