<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="Resultado de verificación documental de SDAYA.">
    <title>Verificación de documento | SDAYA Cartas</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
    <a href="#contenido" class="skip-link">Saltar al contenido principal</a>

    <header class="border-b border-slate-200/80 bg-white/95 backdrop-blur-xl">
        <div class="mx-auto flex min-h-20 max-w-6xl items-center justify-between gap-5 px-4 sm:px-6">
            <a href="{{ route('inicio') }}" class="focus-ring rounded-xl" aria-label="Volver al inicio"><x-application-logo /></a>
            <a href="{{ route('inicio') }}#verificar" class="btn-secondary"><x-icon name="search" size="17" /> <span class="hidden sm:inline">Nueva consulta</span></a>
        </div>
    </header>

    <main id="contenido" class="sdaya-grid min-h-[calc(100vh-5rem)] px-4 py-10 sm:px-6 sm:py-16" tabindex="-1">
        <div class="mx-auto max-w-4xl reveal">
            @if (! $documento)
                <section class="card-elevated overflow-hidden" role="alert">
                    <div class="h-2 bg-red-600"></div>
                    <div class="p-6 text-center sm:p-12">
                        <span class="mx-auto grid size-18 place-items-center rounded-full bg-red-50 text-red-700 ring-8 ring-red-50/60"><x-icon name="alert-circle" size="34" /></span>
                        <p class="eyebrow mt-7 text-red-700">Código no válido</p>
                        <h1 class="mt-3 text-3xl font-black tracking-[-0.03em] text-sdaya-950 sm:text-4xl">Documento no encontrado</h1>
                        <p class="mx-auto mt-4 max-w-xl leading-7 text-slate-600">El código ingresado no corresponde a un documento emitido por SDAYA. Comprueba que hayas copiado los 64 caracteres completos.</p>
                        <a href="{{ route('inicio') }}#verificar" class="btn-primary mt-8"><x-icon name="search" size="18" /> Intentar otra vez</a>
                    </div>
                </section>
            @else
                @php
                    $esValido = $documento->estaEmitido() && $contenidoIntegro && $pdfIntegro;
                    $esAnulado = $documento->estaAnulado();
                @endphp

                <article class="card-elevated overflow-hidden">
                    <header class="relative overflow-hidden {{ $esValido ? 'bg-emerald-700' : 'bg-red-700' }} p-6 text-white sm:p-9" role="status">
                        <div class="pointer-events-none absolute -top-20 -right-14 size-64 rounded-full border border-white/10" aria-hidden="true"></div>
                        <div class="pointer-events-none absolute -top-9 -right-3 size-40 rounded-full border border-white/10" aria-hidden="true"></div>
                        <div class="relative flex flex-col gap-6 sm:flex-row sm:items-center">
                            <span class="grid size-17 shrink-0 place-items-center rounded-2xl bg-white/15 text-white ring-1 ring-white/20">
                                <x-icon :name="$esValido ? 'check-circle' : 'alert-circle'" size="34" />
                            </span>
                            <div>
                                <p class="eyebrow {{ $esValido ? 'text-emerald-100' : 'text-red-100' }}">Verificación oficial SDAYA</p>
                                <h1 class="mt-2 text-2xl font-black tracking-tight sm:text-3xl">{{ $esValido ? 'Documento auténtico' : ($esAnulado ? 'Documento anulado' : 'Integridad no confirmada') }}</h1>
                                <p class="mt-3 max-w-2xl text-sm leading-6 {{ $esValido ? 'text-emerald-50' : 'text-red-50' }}">
                                    {{ $esValido ? 'El registro se encuentra emitido y sus datos coinciden con el archivo PDF oficial.' : ($esAnulado ? 'Este documento fue emitido, pero posteriormente quedó anulado por SDAYA.' : 'El registro existe, pero una comprobación de integridad no coincide. Contacta a SDAYA.') }}
                                </p>
                            </div>
                        </div>
                    </header>

                    <div class="p-5 sm:p-8">
                        <section class="rounded-2xl border border-sdaya-200 bg-sdaya-50/70 p-5 sm:p-6" aria-labelledby="folio-titulo">
                            <p id="folio-titulo" class="detail-term text-sdaya-600">Folio de verificación</p>
                            <div class="mt-2 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <p class="break-all font-mono text-xl font-black tracking-tight text-sdaya-950 sm:text-2xl">{{ $documento->cite }}</p>
                                <x-estado-badge :estado="$documento->estado" />
                            </div>
                        </section>

                        <section class="mt-7" aria-labelledby="datos-titulo">
                            <div class="flex items-center gap-3">
                                <span class="grid size-9 place-items-center rounded-xl bg-sdaya-50 text-sdaya-700"><x-icon name="file-text" size="18" /></span>
                                <h2 id="datos-titulo" class="font-black text-sdaya-950">Datos públicos del documento</h2>
                            </div>
                            <dl class="mt-5 grid gap-x-8 gap-y-6 rounded-2xl border border-slate-200 p-5 sm:grid-cols-2 sm:p-6">
                                <div><dt class="detail-term">Fecha del documento</dt><dd class="detail-value">{{ $documento->fecha_documento->format('d/m/Y') }}</dd></div>
                                @if ($documento->emitido_at)<div><dt class="detail-term">Fecha de emisión</dt><dd class="detail-value">{{ $documento->emitido_at->format('d/m/Y H:i') }}</dd></div>@endif
                                <div><dt class="detail-term">Área</dt><dd class="detail-value">{{ $documento->area->codigo }} — {{ $documento->area->nombre }}</dd></div>
                                <div><dt class="detail-term">Tipo</dt><dd class="detail-value">{{ $documento->tipo->codigo }} — {{ $documento->tipo->nombre }}</dd></div>
                                @if (filled($documento->asunto))<div class="sm:col-span-2"><dt class="detail-term">Asunto</dt><dd class="detail-value">{{ $documento->asunto }}</dd></div>@endif
                            </dl>
                        </section>

                        @unless ($esAnulado)
                            <section class="mt-7" aria-labelledby="integridad-titulo">
                                <div class="flex items-center gap-3">
                                    <span class="grid size-9 place-items-center rounded-xl bg-sdaya-50 text-sdaya-700"><x-icon name="shield-check" size="18" /></span>
                                    <h2 id="integridad-titulo" class="font-black text-sdaya-950">Comprobaciones de integridad</h2>
                                </div>
                                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                    <div class="integrity-check {{ $contenidoIntegro ? 'integrity-ok' : 'integrity-fail' }}"><x-icon :name="$contenidoIntegro ? 'check-circle' : 'alert-circle'" size="18" /> Datos del documento</div>
                                    <div class="integrity-check {{ $pdfIntegro ? 'integrity-ok' : 'integrity-fail' }}"><x-icon :name="$pdfIntegro ? 'check-circle' : 'alert-circle'" size="18" /> Archivo PDF</div>
                                </div>
                            </section>
                        @endunless

                        @if ($esValido)
                            <section class="mt-8 border-t border-slate-200 pt-7" aria-labelledby="pdf-titulo">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div><h2 id="pdf-titulo" class="font-black text-sdaya-950">PDF oficial</h2><p class="mt-1 text-xs text-slate-500">Vista del archivo asociado a este registro.</p></div>
                                    <a href="{{ route('verificar.pdf', $documento->hash_verificacion) }}" class="btn-secondary" target="_blank" rel="noopener"><x-icon name="arrow-right" size="17" /> Abrir en otra pestaña</a>
                                </div>
                                <iframe
                                    src="{{ route('verificar.pdf', $documento->hash_verificacion) }}"
                                    title="PDF oficial del documento {{ $documento->cite }}"
                                    class="mt-5 h-[34rem] w-full rounded-2xl border border-slate-300 bg-slate-100"
                                    loading="lazy"
                                ></iframe>
                            </section>
                        @endif
                    </div>
                </article>

                <div class="mx-auto mt-5 flex max-w-2xl items-start justify-center gap-2 text-center text-xs leading-5 text-slate-500">
                    <x-icon name="lock" size="14" class="mt-0.5 shrink-0 text-sdaya-600" />
                    <p>Por privacidad, esta consulta confirma metadatos e integridad sin publicar el destinatario ni el contenido de la carta.</p>
                </div>
            @endif
        </div>
    </main>
</body>
</html>
