@props(['sub' => null])

{{--
    The incepXion wordmark.

    Drop the real logo at public/logo.png (transparent background) and it is used
    automatically; until then this renders the wordmark in type, so the layout is already
    correct and swapping the file changes nothing else.
--}}
@php $logo = public_path('logo.png'); @endphp

@if (file_exists($logo))
    <img src="{{ asset('logo.png') }}" alt="Incepxion Services Inc."
         {{ $attributes->merge(['class' => 'h-9 w-auto']) }}>
@else
    <span {{ $attributes->merge(['class' => 'inline-flex items-baseline gap-1.5 font-extrabold leading-none tracking-tight']) }}>
        <span>
            <span style="background:linear-gradient(95deg,#ff2c78,#a02fd8 55%,#5f18d8);-webkit-background-clip:text;background-clip:text;color:transparent;">incep</span><span
                  class="relative" style="color:#5f18d8;">X</span><span
                  style="background:linear-gradient(95deg,#5f18d8,#7a2dff);-webkit-background-clip:text;background-clip:text;color:transparent;">ion</span>
        </span>
        @if ($sub)
            <span class="text-[0.42em] font-semibold uppercase tracking-[0.18em] text-[#8d7fa6]">{{ $sub }}</span>
        @endif
    </span>
@endif
