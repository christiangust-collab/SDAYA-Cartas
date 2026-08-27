<x-layouts.app>
    <x-slot:title>Catálogos</x-slot:title>
    <x-slot:heading>Catálogos</x-slot:heading>
    <x-slot:subheading>Administra los catálogos institucionales (Tipos de Área y Tipos de Documento).</x-slot:subheading>

    @if ($errors->any())
        <div class="mb-6 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-900" role="alert" data-validation-summary tabindex="-1">
            <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-red-100 text-red-700"><x-icon name="alert-circle" size="18" /></span>
            <div><p class="font-extrabold">No se pudo guardar el catálogo.</p><ul class="mt-2 list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        </div>
    @endif

    <div data-tabs>
        <div class="mb-7 flex gap-2 overflow-x-auto border-b border-slate-200" role="tablist" aria-label="Catálogos disponibles">
            <button type="button" id="tab-areas" class="tab-button" role="tab" aria-selected="true" aria-controls="areas" data-tab="areas"><x-icon name="building" size="17" /> Tipos de Área <span class="tab-count">{{ $areas->count() }}</span></button>
            <button type="button" id="tab-tipos" class="tab-button" role="tab" aria-selected="false" aria-controls="tipos" data-tab="tipos" tabindex="-1"><x-icon name="tag" size="17" /> Tipos de Documento <span class="tab-count">{{ $tipos->count() }}</span></button>
        </div>

        <section id="areas" role="tabpanel" aria-labelledby="tab-areas" data-tab-panel="areas">
            <div class="grid gap-6 xl:grid-cols-[23rem_minmax(0,1fr)]">
                <form method="POST" action="{{ route('catalogos.areas.store') }}" class="card h-fit overflow-hidden" novalidate>
                    @csrf
                    <div class="border-b border-sdaya-100 bg-sdaya-50 p-5 sm:p-6">
                        <span class="grid size-10 place-items-center rounded-xl bg-white text-sdaya-700 shadow-sm ring-1 ring-sdaya-200"><x-icon name="building" size="19" /></span>
                        <h2 class="mt-4 text-lg font-black text-sdaya-950">Registrar nuevo tipo de área</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-500">El código se utilizará para construir el CITE.</p>
                    </div>
                    <div class="space-y-5 p-5 sm:p-6">
                        <div><label for="area-codigo" class="form-label">Código</label><input id="area-codigo" name="codigo" value="{{ old('codigo') }}" class="form-input font-mono uppercase" maxlength="20" placeholder="Ej.: ADM" required><p class="field-note"><x-icon name="info" size="14" /> Breve, reconocible y en mayúsculas.</p></div>
                        <div><label for="area-nombre" class="form-label">Nombre</label><input id="area-nombre" name="nombre" value="{{ old('nombre') }}" class="form-input" maxlength="150" placeholder="Ej.: Administración" required></div>
                        <div><label for="area-descripcion" class="form-label">Descripción <span class="font-normal text-slate-400">(opcional)</span></label><textarea id="area-descripcion" name="descripcion" class="form-textarea min-h-24" maxlength="1000" placeholder="Describe la responsabilidad del área">{{ old('descripcion') }}</textarea></div>
                        <button type="submit" class="btn-primary w-full"><x-icon name="file-plus" size="18" /> Crear tipo de área</button>
                    </div>
                </form>

                <section class="card overflow-hidden" aria-labelledby="areas-registradas">
                    <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-5 sm:px-6">
                        <div><h2 id="areas-registradas" class="font-black text-sdaya-950">Tipos de área registrados</h2><p class="mt-1 text-xs leading-5 text-slate-500">Se desactivan, no se eliminan, para conservar el historial.</p></div>
                        <span class="rounded-full bg-sdaya-50 px-3 py-1 text-xs font-black text-sdaya-700 ring-1 ring-sdaya-200">{{ $areas->count() }}</span>
                    </div>
                    <ul class="divide-y divide-slate-100">
                        @forelse ($areas as $area)
                            <li class="p-5 transition-colors hover:bg-slate-50/60 sm:p-6">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="flex min-w-0 items-start gap-4">
                                        <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-sdaya-50 font-mono text-xs font-black text-sdaya-800 ring-1 ring-sdaya-200">{{ \Illuminate\Support\Str::substr($area->codigo, 0, 3) }}</span>
                                        <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><span class="font-mono font-black text-sdaya-900">{{ $area->codigo }}</span><span class="status-pill {{ $area->activo ? 'status-active' : 'status-inactive' }}">{{ $area->activo ? 'Activa' : 'Inactiva' }}</span></div><p class="mt-2 font-extrabold text-slate-950">{{ $area->nombre }}</p>@if ($area->descripcion)<p class="mt-1 text-sm leading-6 text-slate-500">{{ $area->descripcion }}</p>@endif</div>
                                    </div>
                                    <form method="POST" action="{{ route('catalogos.areas.toggle', $area) }}" class="shrink-0">@csrf @method('PATCH')<button type="submit" class="{{ $area->activo ? 'btn-ghost' : 'btn-secondary' }}" data-confirm="¿Deseas {{ $area->activo ? 'desactivar' : 'activar' }} esta área?">{{ $area->activo ? 'Desactivar' : 'Activar' }}</button></form>
                                </div>
                                <details class="mt-5 overflow-hidden rounded-xl border border-slate-200 bg-white">
                                    <summary class="focus-ring flex cursor-pointer items-center gap-2 rounded-xl px-4 py-3 text-sm font-extrabold text-sdaya-700"><x-icon name="edit" size="16" /> Editar información</summary>
                                    <form method="POST" action="{{ route('catalogos.areas.update', $area) }}" class="grid gap-4 border-t border-slate-200 bg-slate-50/70 p-4 md:grid-cols-2" novalidate>
                                        @csrf @method('PUT')
                                        <div><label for="area-codigo-{{ $area->id }}" class="form-label">Código</label><input id="area-codigo-{{ $area->id }}" name="codigo" value="{{ $area->codigo }}" class="form-input font-mono uppercase" maxlength="20" required @readonly($area->documentos_emitidos_exists) @if($area->documentos_emitidos_exists) aria-describedby="area-codigo-ayuda-{{ $area->id }}" @endif>@if($area->documentos_emitidos_exists)<p id="area-codigo-ayuda-{{ $area->id }}" class="field-note"><x-icon name="lock" size="14" /> Bloqueado porque ya forma parte de un CITE.</p>@endif</div>
                                        <div><label for="area-nombre-{{ $area->id }}" class="form-label">Nombre</label><input id="area-nombre-{{ $area->id }}" name="nombre" value="{{ $area->nombre }}" class="form-input" maxlength="150" required></div>
                                        <div class="md:col-span-2"><label for="area-desc-{{ $area->id }}" class="form-label">Descripción</label><textarea id="area-desc-{{ $area->id }}" name="descripcion" class="form-textarea min-h-20" maxlength="1000">{{ $area->descripcion }}</textarea></div>
                                        <div class="md:col-span-2"><button type="submit" class="btn-secondary"><x-icon name="save" size="17" /> Guardar cambios</button></div>
                                    </form>
                                </details>
                            </li>
                        @empty
                            <li class="p-12 text-center"><span class="mx-auto grid size-12 place-items-center rounded-2xl bg-slate-100 text-slate-400"><x-icon name="building" size="22" /></span><p class="mt-3 text-sm font-bold text-slate-500">Todavía no hay tipos de área.</p></li>
                        @endforelse
                    </ul>
                </section>
            </div>
        </section>

        <section id="tipos" role="tabpanel" aria-labelledby="tab-tipos" data-tab-panel="tipos">
            <div class="grid gap-6 xl:grid-cols-[23rem_minmax(0,1fr)]">
                <form method="POST" action="{{ route('catalogos.tipos.store') }}" class="card h-fit overflow-hidden" novalidate>
                    @csrf
                    <div class="border-b border-sdaya-100 bg-sdaya-50 p-5 sm:p-6">
                        <span class="grid size-10 place-items-center rounded-xl bg-white text-sdaya-700 shadow-sm ring-1 ring-sdaya-200"><x-icon name="tag" size="19" /></span>
                        <h2 class="mt-4 text-lg font-black text-sdaya-950">Registrar nuevo tipo de documento</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-500">Usa un código breve y fácil de reconocer.</p>
                    </div>
                    <div class="space-y-5 p-5 sm:p-6">
                        <div><label for="tipo-codigo" class="form-label">Código</label><input id="tipo-codigo" name="codigo" value="{{ old('codigo') }}" class="form-input font-mono uppercase" maxlength="20" placeholder="Ej.: NE" required><p class="field-note"><x-icon name="info" size="14" /> Formará parte del CITE definitivo.</p></div>
                        <div><label for="tipo-nombre" class="form-label">Nombre</label><input id="tipo-nombre" name="nombre" value="{{ old('nombre') }}" class="form-input" maxlength="120" placeholder="Ej.: Nota externa" required></div>
                        <div><label for="tipo-descripcion" class="form-label">Descripción <span class="font-normal text-slate-400">(opcional)</span></label><textarea id="tipo-descripcion" name="descripcion" class="form-textarea min-h-24" maxlength="1000" placeholder="Describe cuándo se utiliza">{{ old('descripcion') }}</textarea></div>
                        <button type="submit" class="btn-primary w-full"><x-icon name="file-plus" size="18" /> Crear tipo de documento</button>
                    </div>
                </form>

                <section class="card overflow-hidden" aria-labelledby="tipos-registrados">
                    <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-5 sm:px-6">
                        <div><h2 id="tipos-registrados" class="font-black text-sdaya-950">Tipos de documento registrados</h2><p class="mt-1 text-xs leading-5 text-slate-500">Los códigos usados en documentos emitidos permanecen protegidos.</p></div>
                        <span class="rounded-full bg-sdaya-50 px-3 py-1 text-xs font-black text-sdaya-700 ring-1 ring-sdaya-200">{{ $tipos->count() }}</span>
                    </div>
                    <ul class="divide-y divide-slate-100">
                        @forelse ($tipos as $tipo)
                            <li class="p-5 transition-colors hover:bg-slate-50/60 sm:p-6">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="flex min-w-0 items-start gap-4">
                                        <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-sdaya-50 font-mono text-xs font-black text-sdaya-800 ring-1 ring-sdaya-200">{{ \Illuminate\Support\Str::substr($tipo->codigo, 0, 3) }}</span>
                                        <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><span class="font-mono font-black text-sdaya-900">{{ $tipo->codigo }}</span><span class="status-pill {{ $tipo->activo ? 'status-active' : 'status-inactive' }}">{{ $tipo->activo ? 'Activo' : 'Inactivo' }}</span></div><p class="mt-2 font-extrabold text-slate-950">{{ $tipo->nombre }}</p>@if ($tipo->descripcion)<p class="mt-1 text-sm leading-6 text-slate-500">{{ $tipo->descripcion }}</p>@endif</div>
                                    </div>
                                    <form method="POST" action="{{ route('catalogos.tipos.toggle', $tipo) }}" class="shrink-0">@csrf @method('PATCH')<button type="submit" class="{{ $tipo->activo ? 'btn-ghost' : 'btn-secondary' }}" data-confirm="¿Deseas {{ $tipo->activo ? 'desactivar' : 'activar' }} este tipo?">{{ $tipo->activo ? 'Desactivar' : 'Activar' }}</button></form>
                                </div>
                                <details class="mt-5 overflow-hidden rounded-xl border border-slate-200 bg-white">
                                    <summary class="focus-ring flex cursor-pointer items-center gap-2 rounded-xl px-4 py-3 text-sm font-extrabold text-sdaya-700"><x-icon name="edit" size="16" /> Editar información</summary>
                                    <form method="POST" action="{{ route('catalogos.tipos.update', $tipo) }}" class="grid gap-4 border-t border-slate-200 bg-slate-50/70 p-4 md:grid-cols-2" novalidate>
                                        @csrf @method('PUT')
                                        <div><label for="tipo-codigo-{{ $tipo->id }}" class="form-label">Código</label><input id="tipo-codigo-{{ $tipo->id }}" name="codigo" value="{{ $tipo->codigo }}" class="form-input font-mono uppercase" maxlength="20" required @readonly($tipo->documentos_emitidos_exists) @if($tipo->documentos_emitidos_exists) aria-describedby="tipo-codigo-ayuda-{{ $tipo->id }}" @endif>@if($tipo->documentos_emitidos_exists)<p id="tipo-codigo-ayuda-{{ $tipo->id }}" class="field-note"><x-icon name="lock" size="14" /> Bloqueado porque ya forma parte de un CITE.</p>@endif</div>
                                        <div><label for="tipo-nombre-{{ $tipo->id }}" class="form-label">Nombre</label><input id="tipo-nombre-{{ $tipo->id }}" name="nombre" value="{{ $tipo->nombre }}" class="form-input" maxlength="120" required></div>
                                        <div class="md:col-span-2"><label for="tipo-desc-{{ $tipo->id }}" class="form-label">Descripción</label><textarea id="tipo-desc-{{ $tipo->id }}" name="descripcion" class="form-textarea min-h-20" maxlength="1000">{{ $tipo->descripcion }}</textarea></div>
                                        <div class="md:col-span-2"><button type="submit" class="btn-secondary"><x-icon name="save" size="17" /> Guardar cambios</button></div>
                                    </form>
                                </details>
                            </li>
                        @empty
                            <li class="p-12 text-center"><span class="mx-auto grid size-12 place-items-center rounded-2xl bg-slate-100 text-slate-400"><x-icon name="tag" size="22" /></span><p class="mt-3 text-sm font-bold text-slate-500">Todavía no hay tipos.</p></li>
                        @endforelse
                    </ul>
                </section>
            </div>
        </section>
    </div>
</x-layouts.app>
