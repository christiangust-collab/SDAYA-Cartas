@props(['compact' => false])

<span {{ $attributes->class(['inline-flex items-center gap-3']) }}>
    <img
        src="{{ asset('images/brand/logo-sdaya.png') }}"
        alt="{{ $compact ? 'SDAYA' : 'SDAYA - Sistemas, Desarrollo de Aplicaciones y Auditoría' }}"
        class="{{ $compact ? 'h-9' : 'h-12' }} w-auto object-contain"
    >
</span>
