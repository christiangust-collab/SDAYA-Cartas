@php
    $esEdicion = isset($documento);
    $fecha = old('fecha_documento', $esEdicion ? $documento->fecha_documento->format('Y-m-d') : now()->format('Y-m-d'));
    $areaSeleccionada = old('area_id', $esEdicion ? $documento->area_id : '');
    $tipoSeleccionado = old('tipo_id', $esEdicion ? $documento->tipo_id : '');
    $lugar = old('lugar', $esEdicion && $documento->lugar !== null ? $documento->lugar : config('sdaya.marca.lugar', 'La Paz'));
    $contenido = old('contenido', $esEdicion ? $documento->contenido : '');
    $alineacion = old('alineacion_encabezado', $esEdicion ? ($documento->alineacion_encabezado ?? 'right') : 'right');
@endphp

<form
    method="POST"
    action="{{ $esEdicion ? route('documentos.update', $documento) : route('documentos.store') }}"
    class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]"
    data-document-form
    data-preview-url="{{ route('api.correlativo-preview') }}"
    novalidate
>
    @csrf
    @if ($esEdicion) @method('PUT') @endif

    <div class="space-y-6">
        @if ($errors->any())
            <div class="flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-900" role="alert" tabindex="-1" data-validation-summary>
                <span class="mt-0.5 grid size-8 shrink-0 place-items-center rounded-lg bg-red-100 text-red-700"><x-icon name="alert-circle" size="18" /></span>
                <div>
                    <p class="font-extrabold">Revisa los campos marcados antes de continuar.</p>
                    <p class="mt-1 text-red-800">Encontramos {{ $errors->count() }} {{ $errors->count() === 1 ? 'observación' : 'observaciones' }}.</p>
                </div>
            </div>
        @endif

        <section class="card p-5 sm:p-7" aria-labelledby="datos-documento">
            <div class="section-header">
                <span class="section-number" aria-hidden="true">01</span>
                <div>
                    <h2 id="datos-documento" class="text-lg font-black tracking-tight text-sdaya-950">Identificación del documento</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-500">Define la clasificación, la fecha y el lugar de emisión.</p>
                </div>
            </div>

            <div class="mt-6 grid gap-x-5 gap-y-6 md:grid-cols-2">
                <div>
                    <label for="area_id" class="form-label">Área <span aria-hidden="true" class="text-red-600">*</span></label>
                    <select id="area_id" name="area_id" class="form-select" required aria-describedby="area-ayuda area-error">
                        <option value="">Selecciona un área</option>
                        @foreach ($areas as $area)
                            <option value="{{ $area->id }}" @selected((string) $areaSeleccionada === (string) $area->id)>{{ $area->codigo }} — {{ $area->nombre }}</option>
                        @endforeach
                    </select>
                    <p id="area-ayuda" class="field-note"><x-icon name="building" size="14" /> Unidad responsable del documento.</p>
                    <x-input-error id="area-error" :messages="$errors->get('area_id')" />
                </div>

                <div>
                    <label for="tipo_id" class="form-label">Tipo de documento <span aria-hidden="true" class="text-red-600">*</span></label>
                    <select id="tipo_id" name="tipo_id" class="form-select" required aria-describedby="tipo-ayuda tipo-error">
                        <option value="">Selecciona un tipo</option>
                        @foreach ($tipos as $tipo)
                            <option value="{{ $tipo->id }}" @selected((string) $tipoSeleccionado === (string) $tipo->id)>{{ $tipo->codigo }} — {{ $tipo->nombre }}</option>
                        @endforeach
                    </select>
                    <p id="tipo-ayuda" class="field-note"><x-icon name="tag" size="14" /> Categoría que formará parte del CITE.</p>
                    <x-input-error id="tipo-error" :messages="$errors->get('tipo_id')" />
                </div>

                <div>
                    <label for="fecha_documento" class="form-label">Fecha del documento <span aria-hidden="true" class="text-red-600">*</span></label>
                    <input id="fecha_documento" name="fecha_documento" type="date" value="{{ $fecha }}" class="form-input" required aria-describedby="fecha-error">
                    <x-input-error id="fecha-error" :messages="$errors->get('fecha_documento')" />
                </div>

                <div>
                    <label for="lugar" class="form-label">Lugar de emisión</label>
                    <input id="lugar" name="lugar" type="text" value="{{ $lugar }}" class="form-input" maxlength="80" placeholder="Ciudad desde la que se emite" aria-describedby="lugar-ayuda lugar-error">
                    <p id="lugar-ayuda" class="field-note"><x-icon name="map-pin" size="14" /> Aparecerá junto a la fecha en el encabezado.</p>
                    <x-input-error id="lugar-error" :messages="$errors->get('lugar')" />
                </div>
            </div>
        </section>

        <section class="card p-5 sm:p-7" aria-labelledby="encabezado-titulo">
            <div class="section-header">
                <span class="section-number" aria-hidden="true">02</span>
                <div>
                    <h2 id="encabezado-titulo" class="text-lg font-black tracking-tight text-sdaya-950">Encabezado del documento</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-500">Define la posición del bloque Lugar / Fecha / CITE en el documento.</p>
                </div>
            </div>
            <div class="mt-6">
                <label class="form-label">Alineación del encabezado (Lugar / Fecha / CITE)</label>
                <input type="hidden" name="alineacion_encabezado" id="alineacion_encabezado" value="{{ $alineacion }}">
                <div class="flex gap-2" role="group" aria-label="Alineación del encabezado">
                    @foreach (['left' => ['Izquierda', 'align-left'], 'center' => ['Centro', 'align-center'], 'right' => ['Derecha', 'align-right']] as $val => [$etiqueta, $icono])
                        <button type="button"
                            class="align-btn flex flex-1 items-center justify-center gap-2 rounded-xl border px-3 py-2.5 text-sm font-extrabold transition-all"
                            data-align="{{ $val }}"
                            aria-pressed="{{ $alineacion === $val ? 'true' : 'false' }}"
                        >
                            <x-icon name="{{ $icono }}" size="16" />
                            {{ $etiqueta }}
                        </button>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('alineacion_encabezado')" />
            </div>
        </section>

        <section class="card p-5 sm:p-7" aria-labelledby="contenido-titulo">
            <div class="section-header">
                <span class="section-number" aria-hidden="true">03</span>
                <div>
                    <h2 id="contenido-titulo" class="text-lg font-black tracking-tight text-sdaya-950">Contenido de la carta</h2>
                    <p id="contenido-ayuda" class="mt-1 text-sm leading-6 text-slate-500">Redacta el cuerpo del documento con formato enriquecido estilo Microsoft Word (fuentes, tablas, imágenes y alineaciones).</p>
                </div>
            </div>
            <div class="mt-6" data-editor-wrapper
                data-importar-url="{{ route('documentos.importar-docx') }}"
                data-csrf="{{ csrf_token() }}"
            >
                <div class="mb-3 flex items-center justify-between gap-3">
                    <label for="contenido" class="form-label mb-0">Cuerpo de la carta <span aria-hidden="true" class="text-red-600">*</span></label>
                    <div class="flex items-center gap-2">
                        <button type="button" class="btn-ghost inline-flex items-center gap-1.5 text-xs" data-importar-docx aria-label="Importar documento Word">
                            <x-icon name="upload" size="14" /> Importar DOCX
                        </button>
                        <span class="hidden text-[10px] font-bold tracking-wide text-slate-400 uppercase sm:inline">Editor Profesional Word</span>
                    </div>
                </div>
                <div class="document-sheet-wrapper">
                    <div class="w-full max-w-4xl bg-white">
                        <div data-ckeditor-target aria-label="Editor enriquecido del cuerpo de la carta"></div>
                    </div>
                </div>
                <textarea id="contenido" name="contenido" class="hidden" required aria-describedby="contenido-ayuda contenido-error" data-editor-textarea>{{ $contenido }}</textarea>
                <x-input-error id="contenido-error" :messages="$errors->get('contenido')" />
            </div>

            {{-- Modal importar DOCX --}}
            <dialog id="modal-importar-docx" class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl backdrop:bg-slate-900/50">
                <h2 class="text-lg font-black text-sdaya-950">Importar documento Word</h2>
                <p class="mt-1 text-sm text-slate-500">Selecciona un archivo <code>.docx</code>. El contenido se cargará en el editor para que puedas editarlo y emitirlo con los datos institucionales.</p>
                <div class="mt-5">
                    <label for="docx-archivo" class="form-label">Archivo Word (.docx)</label>
                    <input id="docx-archivo" type="file" accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document" class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-sdaya-50 file:px-3 file:py-1.5 file:text-xs file:font-extrabold file:text-sdaya-700">
                    <p id="docx-error" class="mt-2 hidden text-xs font-bold text-red-700" role="alert"></p>
                </div>
                <p id="docx-limitaciones" class="mt-3 rounded-xl bg-amber-50 border border-amber-200 p-3 text-xs leading-5 text-amber-900">
                    <strong>Nota:</strong> Se conservará texto, negrita, cursiva, colores y alineación básica. Estilos avanzados y numeraciones complejas pueden no importarse perfectamente.
                </p>
                <div class="mt-5 flex justify-end gap-3">
                    <button type="button" id="docx-cancelar" class="btn-ghost">Cancelar</button>
                    <button type="button" id="docx-confirmar" class="btn-primary">
                        <x-icon name="upload" size="16" /> Importar
                    </button>
                </div>
            </dialog>
        </section>
    </div>

    <aside class="space-y-5 xl:sticky xl:top-24 xl:self-start">
        <section class="overflow-hidden rounded-2xl border border-sdaya-800 bg-sdaya-950 text-white shadow-xl shadow-sdaya-950/12" aria-labelledby="cite-preview-title">
            <div class="relative overflow-hidden p-6">
                <div class="pointer-events-none absolute -top-14 -right-10 size-40 rounded-full border border-sdaya-400/20" aria-hidden="true"></div>
                <div class="pointer-events-none absolute -top-6 -right-2 size-24 rounded-full border border-sdaya-400/20" aria-hidden="true"></div>
                <span class="grid size-10 place-items-center rounded-xl bg-white/10 text-sdaya-300 ring-1 ring-white/10"><x-icon name="file-text" size="20" /></span>
                <p id="cite-preview-title" class="eyebrow mt-5 text-sdaya-300">Vista previa del CITE</p>
                <p class="mt-3 break-all font-mono text-xl leading-8 font-black" data-cite-preview>{{ $esEdicion && $documento->cite ? $documento->cite : 'Selecciona área, tipo y fecha' }}</p>
            </div>
            <div class="border-t border-white/10 bg-white/5 p-5">
                <p class="flex items-start gap-2 text-xs leading-5 text-sdaya-100" data-cite-message><x-icon name="info" size="15" class="mt-0.5" /> <span>El número es informativo y solo se reserva al emitir.</span></p>
            </div>
        </section>

        <section class="card p-5" aria-labelledby="acciones-titulo">
            <div class="flex items-center gap-3">
                <span class="grid size-9 place-items-center rounded-xl bg-sdaya-50 text-sdaya-700"><x-icon name="send" size="18" /></span>
                <div><h2 id="acciones-titulo" class="font-black text-sdaya-950">Guardar documento</h2><p class="mt-0.5 text-xs text-slate-500">Elige cómo continuar.</p></div>
            </div>
            <div class="mt-5 space-y-3">
                <button type="submit" name="accion" value="guardar" class="btn-secondary w-full"><x-icon name="save" size="18" /> Guardar borrador</button>
                <button type="submit" name="accion" value="emitir" class="btn-primary w-full" data-confirm="Al emitir se reservará el correlativo y el documento ya no podrá editarse. ¿Deseas continuar?"><x-icon name="send" size="18" /> Guardar y emitir</button>
                <a href="{{ $esEdicion ? route('documentos.show', $documento) : route('documentos.index') }}" class="btn-ghost w-full"><x-icon name="x" size="17" /> Cancelar</a>
            </div>
            <div class="mt-5 rounded-xl bg-slate-50 p-3.5">
                <p class="flex items-start gap-2 text-xs leading-5 text-slate-500"><x-icon name="info" size="15" class="mt-0.5 shrink-0 text-sdaya-600" /> Emitir genera PDF, Word y QR, y bloquea la edición del documento.</p>
            </div>
        </section>

        <p class="px-2 text-center text-[10px] font-bold tracking-wide text-slate-400 uppercase"><span class="text-red-600">*</span> Campos obligatorios</p>
    </aside>
</form>
