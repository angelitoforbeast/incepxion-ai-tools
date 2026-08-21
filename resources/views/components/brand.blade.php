@props([
    'dark' => false,   // sitting on a dark surface (the sidebar)
    'class' => 'h-8',
])

{{--
    The Incepxion wordmark.

    Half of it — "Xion" and "SERVICES INC." — is near-black, so on a dark surface it simply
    disappears. Rather than recolour the mark and drift from the brand, dark placements set
    it on a light plate and keep the artwork exactly as drawn.
--}}
@if ($dark)
    <span class="inline-flex items-center rounded-xl bg-white px-3 py-2 shadow-sm">
        <img src="{{ asset('logo.png') }}" alt="Incepxion Services Inc." class="{{ $class }} w-auto">
    </span>
@else
    <img src="{{ asset('logo.png') }}" alt="Incepxion Services Inc."
         {{ $attributes->merge(['class' => $class.' w-auto']) }}>
@endif
