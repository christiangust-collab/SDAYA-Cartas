@props(['estado'])

@php
    $valor = $estado instanceof \App\Enums\EstadoDocumento ? $estado->value : (string) $estado;
    $etiqueta = $estado instanceof \App\Enums\EstadoDocumento ? $estado->etiqueta() : ucfirst($valor);
    $clases = match ($valor) {
        'emitido' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
        'anulado' => 'border-red-200 bg-red-50 text-red-800',
        default => 'border-amber-200 bg-amber-50 text-amber-900',
    };
@endphp

<span {{ $attributes->class(["inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[10px] font-black tracking-[0.08em] uppercase {$clases}"]) }}>
    <span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>
    {{ $etiqueta }}
</span>
