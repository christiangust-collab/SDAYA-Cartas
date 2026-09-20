<x-layouts.app>
    <x-slot:title>{{ $documento->estaBorrador() ? 'Vista previa: Borrador #'.$documento->id : ($documento->cite ?: 'Documento #'.$documento->id) }}</x-slot:title>
    <x-slot:heading>{{ $documento->estaBorrador() ? 'Vista previa del borrador' : ($documento->cite ?: 'Documento #'.$documento->id) }}</x-slot:heading>
    <x-slot:subheading>{{ $documento->area->nombre }} · {{ $documento->tipo->nombre }}</x-slot:subheading>
    <x-slot:headerActions>
        <a href="{{ route('documentos.index') }}" class="btn-ghost hidden sm:inline-flex"><x-icon name="arrow-left" size="17" /> Volver al listado</a>
        @can('update', $documento)
            <a href="{{ route('documentos.edit', $documento) }}" class="btn-secondary"><x-icon name="edit" size="17" /> Volver a editar</a>
        @endcan
        @can('emitir', $documento)
            @if ($documento->estaBorrador())
                <button type="button" class="btn-primary" onclick="document.getElementById('modal-confirmar-emision').showModal()"><x-icon name="send" size="17" /> Finalizar carta</button>
            @endif
        @endcan
    </x-slot:headerActions>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="space-y-6">
            @if ($documento->estaBorrador())
                <section class="flex flex-col items-start justify-between gap-4 rounded-2xl border border-amber-300 bg-amber-50/95 p-4 sm:p-5 text-amber-950 shadow-sm sm:flex-row sm:items-center" aria-label="Aviso de vista previa de borrador">
                    <div class="flex items-start gap-3.5">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-amber-200/80 text-amber-900 ring-1 ring-amber-300"><x-icon name="eye" size="20" /></span>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center rounded-md bg-amber-200 px-2 py-0.5 text-xs font-black tracking-wide text-amber-900 uppercase">Borrador / Vista Previa</span>
                                <span class="text-xs font-bold text-amber-800">· Documento no finalizado</span>
                            </div>
                            <p class="mt-1 text-sm leading-6 text-amber-900">Esta es una <strong>vista previa de la carta</strong> antes de su finalización oficial. Puedes revisar que los datos y el formato sean correctos o volver a editar si necesitas hacer cambios.</p>
                        </div>
                    </div>
                    <div class="flex w-full shrink-0 flex-wrap gap-2.5 sm:w-auto">
                        @can('update', $documento)
                            <a href="{{ route('documentos.edit', $documento) }}" class="btn-secondary w-full text-sm sm:w-auto"><x-icon name="edit" size="16" /> Volver a editar</a>
                        @endcan
                        @can('emitir', $documento)
                            <button type="button" class="btn-primary w-full text-sm sm:w-auto" onclick="document.getElementById('modal-confirmar-emision').showModal()"><x-icon name="send" size="16" /> Finalizar carta</button>
                        @endcan
                    </div>
                </section>
            @endif

            <article class="document-sheet" aria-labelledby="detalle-titulo">
                <header class="p-5 sm:p-7">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-3">
                                <x-estado-badge :estado="$documento->estado" />
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-bold text-slate-700">
                                    <x-icon name="building" size="13" /> {{ $documento->datosEmpresa()['nombre'] }}
                                </span>
                                <span class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500"><x-icon name="calendar" size="14" /> {{ $documento->fecha_documento->format('d/m/Y') }}</span>
                            </div>
                            <h1 id="detalle-titulo" class="mt-5 text-2xl leading-tight font-black tracking-[-0.025em] text-slate-900 sm:text-3xl">{{ $documento->asunto !== null && $documento->asunto !== '' ? $documento->asunto : ($documento->cite ?: 'Borrador #'.$documento->id) }}</h1>
                            @if (filled($documento->destinatario))
                                <p class="mt-3 flex items-start gap-2 text-sm leading-6 text-slate-600"><x-icon name="user" size="16" class="mt-1 text-slate-500" /> <span>Dirigido a <strong class="text-slate-900">{{ $documento->destinatario }}</strong></span></p>
                            @endif
                        </div>

                        <div class="shrink-0 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 sm:min-w-40 sm:text-right">
                            <p class="detail-term text-slate-600">Clasificación</p>
                            <p class="mt-1.5 font-mono text-base font-black text-slate-900">{{ $documento->area->codigo }} · {{ $documento->tipo->codigo }}</p>
                        </div>
                    </div>
                </header>

                <div class="border-t border-slate-200 bg-slate-50/60 px-5 py-3 sm:px-7">
                    <div class="flex items-center gap-2 text-[10px] font-black tracking-[0.14em] text-slate-500 uppercase"><x-icon name="file-text" size="14" class="text-slate-400" /> Contenido del documento</div>
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
                            <p class="font-bold text-slate-900">CITE: {{ $documento->cite }}</p>
                        @else
                            <p class="font-mono text-xs font-bold text-amber-800">CITE: <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[11px] font-semibold text-amber-900">(Se asignará al finalizar la carta)</span></p>
                        @endif
                    </div>
                </section>

                <section class="min-h-72 bg-white p-5 sm:p-8" aria-label="Contenido de la carta">
                    <div class="document-content">{!! $documento->contenido !!}</div>

                    @php
                        $pieFirma = $documento->pieFirma();
                        $firmaDataUri = $documento->firmaDataUri();
                        $alineacionPieFirma = match($documento->alineacion_pie_firma ?? $documento->alineacion_encabezado ?? 'right') {
                            'left'   => 'text-left',
                            'center' => 'text-center',
                            default  => 'text-right',
                        };
                    @endphp
                    @if ($pieFirma && (!empty($pieFirma['nombre']) || !empty($pieFirma['cargo'])))
                        <div class="mt-12 pt-6 border-t border-slate-200/70 {{ $alineacionPieFirma }}" data-pie-firma>
                            @if ($firmaDataUri)
                                <div class="-mb-3">
                                    <img src="{{ $firmaDataUri }}" alt="Rúbrica de {{ $pieFirma['nombre'] }}" class="h-24 sm:h-28 max-w-72 object-contain inline-block">
                                </div>
                            @endif
                            <p class="font-bold text-slate-900 text-sm sm:text-base leading-tight">{{ $pieFirma['nombre'] }}</p>
                            @if (!empty($pieFirma['cargo']))
                                <p class="font-bold text-slate-900 text-xs sm:text-sm uppercase tracking-tight leading-tight mt-1">{{ $pieFirma['cargo'] }}</p>
                            @endif
                            @if (!empty($pieFirma['empresa']))
                                <p class="font-bold text-slate-900 text-xs tracking-tight uppercase leading-tight mt-1">{{ $pieFirma['empresa'] }}</p>
                            @endif
                            @if (!empty($pieFirma['telefono']))
                                <p class="text-xs text-slate-900 leading-tight mt-1"><span class="font-bold">móvil:</span> {{ $pieFirma['telefono'] }}</p>
                            @endif
                            @if (!empty($pieFirma['correo']))
                                <p class="text-xs text-slate-900 leading-tight mt-0.5"><span class="font-bold">email:</span> {{ $pieFirma['correo'] }}</p>
                            @endif
                        </div>
                    @endif
                </section>
            </article>

            <section class="card p-5 sm:p-7" aria-labelledby="historial-titulo">
                <div class="section-header">
                    <span class="section-number"><x-icon name="history" size="18" /></span>
                    <div>
                        <h2 id="historial-titulo" class="text-lg font-black tracking-tight text-slate-900">Historial del documento</h2>
                        <p class="mt-1 text-sm text-slate-500">Registro cronológico de las acciones realizadas.</p>
                    </div>
                </div>

                <ol class="mt-6 space-y-0">
                    @forelse ($documento->eventos as $evento)
                        <li class="relative grid grid-cols-[2rem_minmax(0,1fr)] gap-4 pb-6 last:pb-0">
                            <span class="relative z-10 grid size-8 place-items-center rounded-xl bg-slate-100 text-slate-700 ring-1 ring-slate-200" aria-hidden="true"><x-icon name="check" size="15" /></span>
                            @if (! $loop->last)<span class="absolute top-8 bottom-0 left-[0.98rem] w-px bg-slate-200" aria-hidden="true"></span>@endif
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
            @if ($documento->estaBorrador())
                <section class="card overflow-hidden border-amber-300" aria-labelledby="acciones-borrador-titulo">
                    <div class="border-b border-amber-200 bg-amber-50 p-5">
                        <span class="grid size-10 place-items-center rounded-xl bg-white text-amber-800 shadow-sm ring-1 ring-amber-300"><x-icon name="eye" size="19" /></span>
                        <h2 id="acciones-borrador-titulo" class="mt-4 font-black text-slate-900">Acciones del borrador</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Revisa la carta antes de finalizar o realiza ajustes si es necesario.</p>
                    </div>
                    <div class="space-y-3 p-5">
                        @can('emitir', $documento)
                            <button type="button" class="btn-primary w-full" onclick="document.getElementById('modal-confirmar-emision').showModal()">
                                <x-icon name="send" size="18" /> Finalizar carta
                            </button>
                        @endcan
                        @can('update', $documento)
                            <a href="{{ route('documentos.edit', $documento) }}" class="btn-secondary w-full"><x-icon name="edit" size="18" /> Volver a editar</a>
                        @endcan

                        <div class="pt-3 border-t border-slate-100">
                            <p class="text-[10px] font-black tracking-wider text-slate-500 uppercase mb-2">Revisión previa del borrador</p>
                            <div class="space-y-1.5">
                                <a href="{{ route('documentos.descargar', [$documento, 'pdf']) }}?ver=1" target="_blank" rel="noopener" class="btn-ghost w-full justify-start text-xs font-bold text-slate-700 hover:text-slate-950">
                                    <x-icon name="eye" size="16" class="text-slate-500" /> Ver borrador en navegador
                                </a>
                                <a href="{{ route('documentos.descargar', [$documento, 'pdf']) }}" class="btn-ghost w-full justify-start text-xs font-bold text-slate-700 hover:text-slate-950">
                                    <x-icon name="download" size="16" class="text-slate-500" /> Descargar borrador en PDF
                                </a>
                                <a href="{{ route('documentos.descargar', [$documento, 'docx']) }}" class="btn-ghost w-full justify-start text-xs font-bold text-slate-700 hover:text-slate-950">
                                    <x-icon name="file-text" size="16" class="text-slate-500" /> Descargar borrador en Word (.docx)
                                </a>
                                <a href="{{ route('documentos.descargar', [$documento, 'pdf']) }}?preimpreso=1&ver=1" target="_blank" rel="noopener" class="btn-ghost w-full justify-start text-xs font-bold text-amber-900 bg-amber-50/60 hover:bg-amber-100/70">
                                    <x-icon name="printer" size="16" class="text-amber-700" /> Probar en hoja membretada física
                                </a>
                            </div>
                        </div>

                        <a href="{{ route('documentos.index') }}" class="btn-ghost w-full"><x-icon name="arrow-left" size="17" /> Volver al listado</a>
                    </div>
                    <div class="border-t border-slate-100 bg-slate-50/80 p-4">
                        <p class="flex items-start gap-2 text-xs leading-5 text-slate-500"><x-icon name="info" size="15" class="mt-0.5 shrink-0 text-slate-400" /> Al pulsar "Finalizar carta" se asignará el CITE correlativo oficial y se generarán los archivos sellados con firma y QR.</p>
                    </div>
                </section>
            @endif

            @if ($documento->estado->estaPublicado())
                <section class="card p-5 text-center" aria-labelledby="verificacion-titulo">
                    <span class="mx-auto grid size-10 place-items-center rounded-xl bg-slate-100 text-slate-700 ring-1 ring-slate-200"><x-icon name="shield-check" size="20" /></span>
                    <h2 id="verificacion-titulo" class="mt-4 font-black text-slate-900">Verificación pública</h2>
                    <p class="mt-1 text-xs leading-5 text-slate-500">Escanea el QR para comprobar este documento.</p>
                    <div class="mx-auto mt-5 w-fit rounded-2xl border border-slate-200 bg-slate-50 p-2.5 shadow-sm">
                        <img src="{{ route('documentos.qr', $documento) }}" alt="Código QR para verificar {{ $documento->cite }}" class="size-40">
                    </div>
                    <p class="mt-4 break-all font-mono text-[9px] leading-4 text-slate-400">{{ $documento->hash_verificacion }}</p>
                    <a href="{{ route('verificar.show', $documento->hash_verificacion) }}" class="btn-secondary mt-5 w-full" target="_blank" rel="noopener">Abrir verificación <x-icon name="arrow-right" size="17" /></a>
                </section>

                <section class="card p-5" aria-labelledby="archivos-titulo">
                    <div class="flex items-center gap-3">
                        <span class="grid size-9 place-items-center rounded-xl bg-slate-100 text-slate-700"><x-icon name="download" size="18" /></span>
                        <div><h2 id="archivos-titulo" class="font-black text-slate-900">Archivos oficiales</h2><p class="mt-0.5 text-xs text-slate-500">Versiones generadas al emitir.</p></div>
                    </div>
                    <div class="mt-5 space-y-2.5">
                        <a href="{{ route('documentos.descargar', [$documento, 'pdf']) }}" class="btn-primary w-full"><x-icon name="download" size="18" /> Descargar PDF oficial</a>
                        <a href="{{ route('verificar.pdf', $documento->hash_verificacion) }}" target="_blank" rel="noopener" class="btn-secondary w-full"><x-icon name="eye" size="18" /> Ver PDF en navegador</a>
                        <a href="{{ route('documentos.descargar', [$documento, 'docx']) }}" class="btn-ghost w-full"><x-icon name="file-text" size="18" /> Descargar Word (.docx)</a>
                        <a href="{{ route('documentos.descargar', [$documento, 'pdf']) }}?preimpreso=1" class="btn-ghost w-full border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50">
                            <x-icon name="printer" size="16" class="text-slate-500" /> PDF para papel preimpreso (sin logo)
                        </a>
                    </div>
                    @if ($documento->emitido_at)
                        <p class="mt-5 border-t border-slate-200 pt-4 text-xs leading-5 text-slate-500">Emitido el {{ $documento->emitido_at->format('d/m/Y H:i') }} por <strong class="text-slate-700">{{ $documento->emisor?->name ?? 'usuario no disponible' }}</strong>.</p>
                    @endif
                </section>
            @endif

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

    @if ($documento->estaBorrador())
        <dialog id="modal-confirmar-emision" class="w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-6 sm:p-7 shadow-2xl backdrop:bg-slate-900/60">
            <div class="flex items-start gap-4">
                <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-sdaya-50 text-sdaya-600 ring-1 ring-sdaya-200">
                    <x-icon name="shield-check" size="26" />
                </span>
                <div>
                    <h2 class="text-lg font-black text-slate-900 leading-tight">Confirmar emisión de carta oficial</h2>
                    <p class="mt-1 text-xs text-slate-500 leading-relaxed">Verifica los datos institucionales antes de finalizar. Una vez emitida, la carta quedará sellada digitalmente y no podrá ser editada.</p>
                </div>
            </div>

            <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50/90 p-4 space-y-2.5 text-xs">
                <div class="flex justify-between border-b border-slate-200/80 pb-2">
                    <span class="font-bold text-slate-500">Institución / Empresa:</span>
                    <span class="font-extrabold text-slate-900 text-right">{{ $documento->datosEmpresa()['nombre'] }}</span>
                </div>
                <div class="flex justify-between border-b border-slate-200/80 pb-2">
                    <span class="font-bold text-slate-500">Clasificación:</span>
                    <span class="font-mono font-black text-slate-900">{{ $documento->area->codigo }} · {{ $documento->tipo->codigo }} (Gestión {{ $documento->anio }})</span>
                </div>
                @if (filled($documento->destinatario))
                    <div class="flex justify-between border-b border-slate-200/80 pb-2">
                        <span class="font-bold text-slate-500">Destinatario:</span>
                        <span class="font-bold text-slate-900 text-right max-w-xs">{{ $documento->destinatario }}</span>
                    </div>
                @endif
                @if (filled($documento->asunto))
                    <div class="flex justify-between border-b border-slate-200/80 pb-2">
                        <span class="font-bold text-slate-500">Referencia:</span>
                        <span class="font-bold text-slate-900 text-right max-w-xs">{{ $documento->asunto }}</span>
                    </div>
                @endif
                <div class="flex justify-between pt-1">
                    <span class="font-bold text-slate-500">Correlativo CITE:</span>
                    <span class="font-bold text-emerald-800 bg-emerald-100/90 px-2 py-0.5 rounded text-[11px]">Se reservará el número correlativo oficial</span>
                </div>
            </div>

            <div class="mt-4 rounded-xl bg-amber-50 border border-amber-200 p-3 text-xs leading-5 text-amber-950 flex items-start gap-2.5">
                <x-icon name="alert-circle" size="16" class="shrink-0 text-amber-700 mt-0.5" />
                <span><strong>Aviso:</strong> Se generarán de inmediato los archivos definitivos en PDF y Word, junto al código QR con firma de verificación pública.</span>
            </div>

            <div class="mt-6 flex flex-col-reverse sm:flex-row justify-end gap-3">
                <button type="button" class="btn-ghost w-full sm:w-auto" onclick="document.getElementById('modal-confirmar-emision').close()">
                    Volver a revisar
                </button>
                <form method="POST" action="{{ route('documentos.emitir', $documento) }}" class="w-full sm:w-auto">
                    @csrf
                    <button type="submit" class="btn-primary w-full sm:w-auto">
                        <x-icon name="send" size="17" /> Confirmar y Emitir Carta
                    </button>
                </form>
            </div>
        </dialog>
    @endif
</x-layouts.app>

