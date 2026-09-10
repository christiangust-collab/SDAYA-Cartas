<x-layouts.app>
    <x-slot:title>Mi Perfil y Pie de Firma</x-slot:title>
    <x-slot:heading>Mi Perfil y Pie de Firma</x-slot:heading>
    <x-slot:subheading>Configura tus datos institucionales para el pie de firma automático de las cartas.</x-slot:subheading>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="space-y-6">
            <form method="POST" action="{{ route('perfil.update') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')

                <section class="card p-5 sm:p-7" aria-labelledby="perfil-datos-titulo">
                    <div class="section-header">
                        <span class="section-number"><x-icon name="user" size="18" /></span>
                        <div>
                            <h2 id="perfil-datos-titulo" class="text-lg font-black tracking-tight text-sdaya-950">Datos del Firmante</h2>
                            <p class="mt-1 text-sm text-slate-500">Estos datos se utilizarán automáticamente cuando figures como firmante de una carta.</p>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-x-5 gap-y-6 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label for="name" class="form-label">Nombre completo y título <span aria-hidden="true" class="text-red-600">*</span></label>
                            <input id="name" name="name" type="text" value="{{ old('name', $usuario->name) }}" class="form-input" required maxlength="150" placeholder="Ej. Ing. Juan Pérez">
                            <p class="field-note"><x-icon name="user" size="14" /> Incluye grado académico o título si corresponde.</p>
                            <x-input-error :messages="$errors->get('name')" />
                        </div>

                        <div>
                            <label for="email" class="form-label">Correo electrónico institucional</label>
                            <input id="email" type="email" value="{{ $usuario->email }}" class="form-input" readonly disabled>
                            <p class="field-note"><x-icon name="mail" size="14" /> Correo asignado a tu cuenta de acceso.</p>
                        </div>

                        <div>
                            <label for="cargo" class="form-label">Cargo institucional</label>
                            <input id="cargo" name="cargo" type="text" value="{{ old('cargo', $usuario->cargo) }}" class="form-input" maxlength="150" placeholder="Ej. Gerente Administrativo">
                            <p class="field-note"><x-icon name="briefcase" size="14" /> Puesto oficial en la organización.</p>
                            <x-input-error :messages="$errors->get('cargo')" />
                        </div>

                        <div>
                            <label for="empresa_id" class="form-label">Empresa Institucional</label>
                            @if (isset($empresas) && $empresas->isNotEmpty())
                                <select id="empresa_id" name="empresa_id" class="form-select">
                                    @foreach ($empresas as $emp)
                                        <option value="{{ $emp->id }}" @selected((string) old('empresa_id', $usuario->empresa_id) === (string) $emp->id)>{{ $emp->nombre }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input id="empresa" name="empresa" type="text" value="{{ old('empresa', $usuario->empresaInstitucion?->nombre ?? ($usuario->empresa ?? config('sdaya.marca.nombre', 'SDAYA S.R.L.'))) }}" class="form-input" maxlength="150" placeholder="Ej. SDAYA S.R.L.">
                            @endif
                            <p class="field-note"><x-icon name="building" size="14" /> Empresa a la que perteneces para la firma de cartas.</p>
                            <x-input-error :messages="$errors->get('empresa_id')" />
                        </div>

                        <div>
                            <label for="telefono" class="form-label">Teléfono / Celular de contacto</label>
                            <input id="telefono" name="telefono" type="text" value="{{ old('telefono', $usuario->telefono) }}" class="form-input" maxlength="50" placeholder="Ej. +591 70000000">
                            <p class="field-note"><x-icon name="phone" size="14" /> Número de contacto para el pie de firma.</p>
                            <x-input-error :messages="$errors->get('telefono')" />
                        </div>

                        <div class="md:col-span-2 border-t border-slate-200/80 pt-5">
                            <label for="firma_digital" class="form-label">Rúbrica / Firma digitalizada (PNG o JPG)</label>
                            <div class="mt-2 flex flex-col gap-4 sm:flex-row sm:items-center">
                                @php $firmaUri = $usuario->firmaDataUri(); @endphp
                                @if ($firmaUri)
                                    <div class="relative flex items-center justify-center rounded-xl border border-dashed border-sdaya-300 bg-slate-50 p-3 h-20 w-44 shrink-0">
                                        <img src="{{ $firmaUri }}" alt="Rúbrica actual" class="max-h-full max-w-full object-contain">
                                    </div>
                                @endif
                                <div class="flex-1 space-y-2">
                                    <input id="firma_digital" name="firma_digital" type="file" accept="image/png,image/jpeg,image/webp" class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-extrabold file:bg-sdaya-100 file:text-sdaya-900 hover:file:bg-sdaya-200 cursor-pointer">
                                    <p class="field-note"><x-icon name="image" size="14" /> Recomendado: Imagen PNG con fondo transparente (máx. 2 MB). Se posicionará sobre tu nombre en el pie de firma.</p>
                                    <x-input-error :messages="$errors->get('firma_digital')" />
                                </div>
                            </div>
                            @if ($firmaUri)
                                <div class="mt-3 flex items-center gap-2">
                                    <label class="inline-flex items-center gap-2 text-xs font-semibold text-red-600 hover:text-red-800 cursor-pointer">
                                        <input type="checkbox" name="eliminar_firma" value="1" class="rounded border-slate-300 text-red-600 focus:ring-red-500">
                                        Eliminar firma actual y dejar solo texto
                                    </label>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end border-t border-slate-200/80 pt-5">
                        <button type="submit" class="btn-primary">
                            <x-icon name="save" size="17" /> Guardar cambios
                        </button>
                    </div>
                </section>
            </form>
        </div>

        <aside class="space-y-5 xl:sticky xl:top-24 xl:self-start">
            <section class="card overflow-hidden border-sdaya-200" aria-labelledby="preview-firma-titulo">
                <div class="border-b border-sdaya-100 bg-sdaya-50 p-5">
                    <span class="grid size-10 place-items-center rounded-xl bg-white text-sdaya-700 shadow-sm ring-1 ring-sdaya-200"><x-icon name="award" size="19" /></span>
                    <h2 id="preview-firma-titulo" class="mt-4 font-black text-sdaya-950">Vista previa del pie de firma</h2>
                    <p class="mt-1 text-xs leading-5 text-slate-500">Así se visualizará el bloque al pie de tus cartas.</p>
                </div>
                <div class="p-5 bg-white text-right space-y-0.5">
                    @php $firmaPreview = $usuario->firmaDataUri(); @endphp
                    @if ($firmaPreview)
                        <div class="flex justify-end -mb-3">
                            <img src="{{ $firmaPreview }}" alt="Firma" class="h-16 max-w-48 object-contain">
                        </div>
                    @endif
                    <p class="font-bold text-slate-900 text-sm leading-tight">{{ $usuario->name }}</p>
                    @if ($usuario->cargo)
                        <p class="text-xs font-bold text-slate-900 uppercase leading-tight">{{ $usuario->cargo }}</p>
                    @endif
                    <p class="text-[11px] font-bold text-slate-900 uppercase leading-tight">{{ $usuario->empresaInstitucion?->nombre ?: ($usuario->empresa ?: config('sdaya.marca.nombre', 'SDAYA S.R.L.')) }}</p>
                    @if ($usuario->telefono)
                        <p class="text-xs text-slate-900 leading-tight pt-0.5"><span class="font-bold">móvil:</span> {{ $usuario->telefono }}</p>
                    @endif
                    <p class="text-xs text-slate-900 leading-tight"><span class="font-bold">email:</span> {{ $usuario->email }}</p>
                </div>
                <div class="border-t border-slate-100 bg-slate-50/80 p-4">
                    <p class="flex items-start gap-2 text-xs leading-5 text-slate-500"><x-icon name="info" size="15" class="mt-0.5 shrink-0 text-sdaya-600" /> Los cambios aplicados se reflejarán en las nuevas cartas y borradores. Las cartas ya finalizadas conservarán su copia histórica original.</p>
                </div>
            </section>
        </aside>
    </div>
</x-layouts.app>
