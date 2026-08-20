@props(['model', 'min' => null, 'max' => null, 'size' => 'text-xs'])

{{--
    A native date input keeps the calendar popup and validation, but always renders the
    browser's numeric locale format (08/06/2026). The real value is hidden and a readable
    "Aug 6, 2026" is drawn over it instead; the field underneath still handles every click.
--}}
<div class="relative" x-data="{
        value: @entangle($model),
        pretty() {
            if (! this.value) return '';
            const d = new Date(this.value + 'T00:00:00');
            return isNaN(d) ? this.value
                : d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        },
     }">
    <input type="date" wire:model.live="{{ $model }}"
           @if ($min) min="{{ $min }}" @endif
           @if ($max) max="{{ $max }}" @endif
           {{ $attributes->merge(['class' => 'w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500 text-transparent']) }}>
    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-800 {{ $size }}"
          x-text="pretty()"></span>
</div>
