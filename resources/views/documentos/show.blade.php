<x-layouts.app>
    <x-slot:title>{{ $documento->cite ?: 'Borrador #'.$documento->id }}</x-slot:title>
    <x-slot:heading>{{ $documento->cite ?: 'Borrador #'.$documento->id }}</x-slot:heading>
    <x-slot:subheading>{{ $documento->area->nombre }} · {{ $documento->tipo->nombre }}</x-slot:subheading>
    <x-slot:headerActions>
        <a href="{{ route('documentos.index') }}" class="btn-ghost hidden sm:inline-flex"><x-icon name="arrow-left" size="17" /> Volver</a>
        @can('update', $documento)
            <a href="{{ route('documentos.edit', $documento) }}" class="btn-secondary"><x-icon name="edit" size="17" /> Editar</a>
        @endcan
    </x-slot:headerActions>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="space-y-6">
            <article class="document-sheet" aria-labelledby="detalle-titulo">
                <header class="p-5 sm:p-7">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-3">
                                <x-estado-badge :estado="$documento->estado" />
                                <span class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500"><x-icon name="calendar" size="14" /> {{ $documento->fecha_documento->format('d/m/Y') }}</span>
                            </div>
                            <h1 id="detalle-titulo" class="mt-5 text-2xl leading-tight font-black tracking-[-0.025em] text-sdaya-950 sm:text-3xl">{{ $documento->asunto !== null && $documento->asunto !== '' ? $documento->asunto : ($documento->cite ?: 'Borrador #'.$documento->id) }}</h1>
                            @if (filled($documento->destinatario))
                                <p class="mt-3 flex items-start gap-2 text-sm leading-6 text-slate-600"><x-icon name="user" size="16" class="mt-1 text-sdaya-600" /> <span>Dirigido a <strong class="text-slate-900">{{ $documento->destinatario }}</strong></span></p>
                            @endif
                        </div>

                        <div class="shrink-0 rounded-2xl border border-sdaya-200 bg-sdaya-50 px-4 py-3.5 sm:min-w-40 sm:text-right">
                            <p class="detail-term text-sdaya-600">Clasificación</p>
                            <p class="mt-1.5 font-mono text-base font-black text-sdaya-950">{{ $documento->area->codigo }} · {{ $documento->tipo->codigo }}</p>
                        </div>
                    </div>
                </header>

                <div class="border-t border-slate-200 bg-slate-50/60 px-5 py-3 sm:px-7">
                    <div class="flex items-center gap-2 text-[10px] font-black tracking-[0.14em] text-slate-500 uppercase"><x-icon name="file-text" size="14" class="text-sdaya-600" /> Contenido del documento</div>
                </div>

                @php
                    $lugarDocumento = filled($documento->lugar) ? $documento->lugar : config('sdaya.marca.lugar', 'La Paz');
                    $alineacionEncabezado = match($documento->alineacion_encabezado ?? 'right') {
                        'left'   => 'text-left',
                        'center' => 'text-center',
                        default  => 'text-right',
                    };
                @endphp
                <section class="bg-white px-5 pt-5 sm:px-8" aria-label="Encabezado del documento">
                    <div class="{{ $alineacionEncabezado }} leading-relaxed text-slate-800">
                        <p>{{ $lugarDocumento }}, {{ $documento->fecha_documento->locale('es')->translatedFormat('d \d\e F \d\e Y') }}</p>
                        @if ($documento->cite)
                            <p class="font-bold text-sdaya-800">CITE: {{ $documento->cite }}</p>
                        @endif
                    </div>
                </section>

                <section class="min-h-72 bg-white p-5 sm:p-8" aria-label="Contenido de la carta">
                    <div class="document-content">{!! $documento->contenido !!}</div>
                </section>
            </article>

            <section class="card p-5 sm:p-7" aria-labelledby="historial-titulo">
                <div class="section-header">
                    <span class="section-number"><x-icon name="history" size="18" /></span>
                    <div>
                        <h2 id="historial-titulo" class="text-lg font-black tracking-tight text-sdaya-950">Historial del documento</h2>
                        <p class="mt-1 text-sm text-slate-500">Registro cronológico de las acciones realizadas.</p>
                    </div>
                </div>

                <ol class="mt-6 space-y-0">
                    @forelse ($documento->eventos as $evento)
                        <li class="relative grid grid-cols-[2rem_minmax(0,1fr)] gap-4 pb-6 last:pb-0">
                            <span class="relative z-10 grid size-8 place-items-center rounded-xl bg-sdaya-50 text-sdaya-700 ring-1 ring-sdaya-200" aria-hidden="true"><x-icon name="check" size="15" /></span>
                            @if (! $loop->last)<span class="absolute top-8 bottom-0 left-[0.98rem] w-px bg-sdaya-200" aria-hidden="true"></span>@endif
                            <div class="pt-1">
                                <p class="text-sm font-extrabold text-slate-900">{{ $evento->evento->etiqueta() }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $evento->usuario?->name ?? 'Usuario no disponible' }} · {{ $evento->created_at->format('d/m/Y H:i') }}</p>
                                @if ($evento->evento === \App\Enums\EventoDocumento::ANULADO && data_get($evento->datos, 'motivo'))
                                    <p class="mt-3 rounded-xl border border-red-100 bg-red-50 p-3.5 text-sm leading-6 text-red-900"><strong>Motivo:</strong> {{ data_get($evento->datos, 'motivo') }}</p>
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="rounded-xl bg-slate-50 p-5 text-sm text-slate-500">No hay eventos registrados.</li>
                    @endforelse
                </ol>
            </section>
        </div>

        <aside class="space-y-5 xl:sticky xl:top-24 xl:self-start">
            @if ($documento->estado->estaPublicado())
                <section class="overflow-hidden rounded-2xl border border-sdaya-800 bg-sdaya-950 text-white shadow-xl shadow-sdaya-950/10" aria-labelledby="verificacion-titulo">
                    <div class="p-5 text-center">
                        <span class="mx-auto grid size-10 place-items-center rounded-xl bg-sdaya-400/15 text-sdaya-300 ring-1 ring-sdaya-400/20"><x-icon name="shield-check" size="20" /></span>
                        <h2 id="verificacion-titulo" class="mt-4 font-black">Verificación pública</h2>
                        <p class="mt-1 text-xs leading-5 text-sdaya-200">Escanea el QR para comprobar este documento.</p>
                        <div class="mx-auto mt-5 w-fit rounded-2xl bg-white p-2.5 shadow-lg">
                            <img src="{{ route('documentos.qr', $documento) }}" alt="Código QR para verificar {{ $documento->cite }}" class="size-40">
                        </div>
                        <p class="mt-4 break-all font-mono text-[9px] leading-4 text-sdaya-300">{{ $documento->hash_verificacion }}</p>
                    </div>
                    <a href="{{ route('verificar.show', $documento->hash_verificacion) }}" class="flex min-h-12 items-center justify-center gap-2 border-t border-white/10 bg-white/5 px-4 text-sm font-extrabold text-white transition-colors hover:bg-white/10 focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-inset focus-visible:ring-sdaya-400" target="_blank" rel="noopener">Abrir verificación <x-icon name="arrow-right" size="17" /></a>
                </section>

                <section class="card p-5" aria-labelledby="archivos-titulo">
                    <div class="flex items-center gap-3">
                        <span class="grid size-9 place-items-center rounded-xl bg-sdaya-50 text-sdaya-700"><x-icon name="download" size="18" /></span>
                        <div><h2 id="archivos-titulo" class="font-black text-sdaya-950">Archivos oficiales</h2><p class="mt-0.5 text-xs text-slate-500">Versiones generadas al emitir.</p></div>
                    </div>
                    <div class="mt-5 space-y-3">
                        <a href="{{ route('documentos.descargar', [$documento, 'pdf']) }}" class="btn-primary w-full"><x-icon name="file-text" size="18" /> Descargar PDF</a>
                        <a href="{{ route('documentos.descargar', [$documento, 'docx']) }}" class="btn-secondary w-full"><x-icon name="download" size="18" /> Descargar Word</a>
                    </div>
                    @if ($documento->emitido_at)
                        <p class="mt-5 border-t border-slate-200 pt-4 text-xs leading-5 text-slate-500">Emitido el {{ $documento->emitido_at->format('d/m/Y H:i') }} por <strong class="text-slate-700">{{ $documento->emisor?->name ?? 'usuario no disponible' }}</strong>.</p>
                    @endif
                </section>
            @endif

            @can('emitir', $documento)
                <section class="card overflow-hidden border-sdaya-200" aria-labelledby="emitir-titulo">
                    <div class="border-b border-sdaya-100 bg-sdaya-50 p-5">
                        <span class="grid size-10 place-items-center rounded-xl bg-white text-sdaya-700 shadow-sm ring-1 ring-sdaya-200"><x-icon name="send" size="19" /></span>
                        <h2 id="emitir-titulo" class="mt-4 font-black text-sdaya-950">Documento listo para emitir</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Se asignará el CITE definitivo y la edición quedará bloqueada.</p>
                    </div>
                    <form method="POST" action="{{ route('documentos.emitir', $documento) }}" class="p-5">
                        @csrf
                        <button type="submit" class="btn-primary w-full" data-confirm="Se asignará el correlativo definitivo y el documento quedará bloqueado. ¿Deseas emitirlo?"><x-icon name="send" size="18" /> Emitir documento</button>
                    </form>
                </section>
            @endcan

            @can('anular', $documento)
                <details class="card overflow-hidden" @if($errors->has('motivo')) open @endif>
                    <summary class="focus-ring flex cursor-pointer items-center gap-3 rounded-xl p-5 font-extrabold text-red-800"><span class="grid size-8 place-items-center rounded-lg bg-red-50"><x-icon name="alert-circle" size="17" /></span> Anular documento</summary>
                    <div class="border-t border-red-100 bg-red-50/40 p-5">
                        <p class="text-sm leading-6 text-slate-600">La anulación será pública y quedará registrada en el historial.</p>
                        <form method="POST" action="{{ route('documentos.anular', $documento) }}" class="mt-4">
                            @csrf
                            <label for="motivo" class="form-label">Motivo de anulación</label>
                            <textarea id="motivo" name="motivo" class="form-textarea min-h-28" minlength="10" maxlength="500" required aria-describedby="motivo-error"></textarea>
                            <x-input-error id="motivo-error" :messages="$errors->get('motivo')" />
                            <button type="submit" class="btn-danger mt-3 w-full" data-confirm="Esta acción marcará el documento como anulado. ¿Deseas continuar?"><x-icon name="alert-circle" size="18" /> Confirmar anulación</button>
                        </form>
                    </div>
                </details>
            @endcan
        </aside>
    </div>
</x-layouts.app>

