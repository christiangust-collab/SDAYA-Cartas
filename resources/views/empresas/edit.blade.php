<x-layouts.app>
    <x-slot:title>Editar Empresa</x-slot:title>
    <x-slot:heading>Editar Empresa: {{ $empresa->nombre }}</x-slot:heading>
    <x-slot:subheading>Modifica los datos institucionales o el logo de la empresa.</x-slot:subheading>

    <div class="mb-6">
        <a href="{{ route('empresas.index') }}" class="btn-ghost inline-flex items-center gap-2 text-xs font-extrabold text-sdaya-700">
            <x-icon name="arrow-left" size="16" /> Volver al listado de empresas
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-6 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-900" role="alert">
            <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-red-100 text-red-700">
                <x-icon name="alert-circle" size="18" />
            </span>
            <div>
                <p class="font-extrabold">Por favor corrige los siguientes errores:</p>
                <ul class="mt-2 list-disc pl-5 font-medium">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('empresas.update', $empresa) }}" enctype="multipart/form-data" class="card max-w-3xl overflow-hidden" novalidate>
        @csrf
        @method('PUT')

        <div class="border-b border-sdaya-100 bg-sdaya-50 p-5 sm:p-6">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="grid size-10 place-items-center rounded-xl bg-white text-sdaya-700 shadow-sm ring-1 ring-sdaya-200">
                        <x-icon name="building" size="20" />
                    </span>
                    <div>
                        <h2 class="text-lg font-black text-sdaya-950">Información Institucional</h2>
                        <p class="text-xs text-slate-500">Los cambios aplicarán a nuevos borradores. Las cartas ya finalizadas conservan su snapshot histórico.</p>
                    </div>
                </div>
                <span class="status-pill {{ $empresa->activo ? 'status-active' : 'status-inactive' }}">
                    {{ $empresa->activo ? 'Activa' : 'Inactiva' }}
                </span>
            </div>
        </div>

        <div class="space-y-6 p-5 sm:p-8">
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="nombre" class="form-label">Nombre o Razón Social <span class="text-red-500">*</span></label>
                    <input id="nombre" name="nombre" type="text" value="{{ old('nombre', $empresa->nombre) }}" class="form-input" maxlength="150" required>
                    <p class="field-note"><x-icon name="info" size="14" /> Nombre que figurará en las notas y pies de firma.</p>
                </div>

                <div>
                    <label for="nit" class="form-label">NIT / Identificación Tributaria</label>
                    <input id="nit" name="nit" type="text" value="{{ old('nit', $empresa->nit) }}" class="form-input" maxlength="50">
                </div>

                <div>
                    <label for="telefono" class="form-label">Teléfono institucional</label>
                    <input id="telefono" name="telefono" type="text" value="{{ old('telefono', $empresa->telefono) }}" class="form-input" maxlength="50">
                </div>

                <div class="sm:col-span-2">
                    <label for="direccion" class="form-label">Dirección física</label>
                    <input id="direccion" name="direccion" type="text" value="{{ old('direccion', $empresa->direccion) }}" class="form-input" maxlength="250">
                </div>

                <div>
                    <label for="correo" class="form-label">Correo electrónico institucional</label>
                    <input id="correo" name="correo" type="email" value="{{ old('correo', $empresa->correo) }}" class="form-input" maxlength="150">
                </div>

                <div>
                    <label for="sitio_web" class="form-label">Sitio web institucional</label>
                    <input id="sitio_web" name="sitio_web" type="url" value="{{ old('sitio_web', $empresa->sitio_web) }}" class="form-input" maxlength="150">
                </div>

                {{-- GESTIÓN DE LOGO / MEMBRETE --}}
                <div class="sm:col-span-2 border-t border-slate-100 pt-5">
                    <label class="form-label">Logo / Membrete de la Empresa</label>
                    @if ($empresa->logoDataUri())
                        <div class="mb-4 flex items-center gap-4 rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <img src="{{ $empresa->logoDataUri() }}" alt="{{ $empresa->nombre }}" class="h-16 w-32 object-contain bg-white rounded-lg p-1 border border-slate-200">
                            <div>
                                <p class="text-xs font-bold text-slate-800">Logo actual configurado</p>
                                <label class="mt-2 flex items-center gap-2 text-xs font-bold text-red-700 cursor-pointer">
                                    <input type="checkbox" name="eliminar_logo" value="1" class="rounded border-slate-300 text-red-600 focus:ring-red-500 size-4">
                                    <span>Eliminar logo actual y usar membrete base</span>
                                </label>
                            </div>
                        </div>
                    @endif

                    <label for="logo" class="form-label text-xs">Reemplazar o subir nuevo logo</label>
                    <input id="logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp" class="form-input text-xs">
                    <p class="field-note"><x-icon name="info" size="14" /> Formato PNG, JPG o WebP. Máximo 2 MB.</p>
                </div>

                <div class="sm:col-span-2 border-t border-slate-100 pt-5">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="activo" value="1" @checked(old('activo', $empresa->activo)) class="rounded border-slate-300 text-sdaya-600 focus:ring-sdaya-500 size-4">
                        <span class="text-sm font-bold text-slate-800">Empresa activa</span>
                    </label>
                    <p class="field-note ml-7">Las empresas inactivas no aparecerán para nuevas cartas ni nuevos firmantes.</p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-6">
                <a href="{{ route('empresas.index') }}" class="btn-ghost">Cancelar</a>
                <button type="submit" class="btn-primary">
                    <x-icon name="save" size="18" /> Guardar Cambios
                </button>
            </div>
        </div>
    </form>
</x-layouts.app>