@props([
    'dark' => false,   // sitting on a dark surface (sidebar, landing nav)
    'class' => 'h-8',
])

{{--
    The Incepxion wordmark.

    Half the artwork — "Xion" and "SERVICES INC." — is near-black and vanishes on a dark
    surface. Rather than boxing it on a white plate, dark placements use a variant with the
    lightness flipped and the hue held, so the pink stays pink and the rest comes up bright
    enough to read. Both files are generated from the same source.
--}}
<img src="{{ asset($dark ? 'logo-light.png' : 'logo.png') }}"
     alt="Incepxion Services Inc."
     {{ $attributes->merge(['class' => $class.' w-auto']) }}>
