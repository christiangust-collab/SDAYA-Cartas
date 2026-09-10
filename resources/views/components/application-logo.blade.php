@props(['compact' => false])

@php
    $logoUri = $empresaActual->logoDataUri();
    $nombre = $empresaActual->nombre_comercial ?: $empresaActual->nombre;
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-2.5']) }}>
    @if ($logoUri)
        <img
            src="{{ $logoUri }}"
            alt="{{ $nombre }}"
            class="{{ $compact ? 'h-9 max-h-9' : 'h-10' }} max-w-36 object-contain"
        >
    @elseif (file_exists(public_path('images/brand/logo-sdaya.png')) && str_contains(strtoupper($empresaActual->nombre), 'SDAYA'))
        <img
            src="{{ asset('images/brand/logo-sdaya.png') }}"
            alt="{{ $nombre }}"
            class="{{ $compact ? 'h-9 max-h-9' : 'h-10' }} max-w-36 object-contain"
        >
    @else
        <span class="grid {{ $compact ? 'size-8 text-xs' : 'size-10 text-sm' }} place-items-center rounded-xl bg-slate-900 font-black text-white shadow-sm">
            {{ $empresaActual->monograma() }}
        </span>
        @if (! $compact)
            <span class="text-sm font-black tracking-tight text-slate-900">{{ $empresaActual->nombre_aplicacion }}</span>
        @endif
    @endif
</span>
