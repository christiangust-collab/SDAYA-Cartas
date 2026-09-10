<x-layouts.app>
    <x-slot:title>Gestión de Empresas</x-slot:title>
    <x-slot:heading>Empresas Institucionales</x-slot:heading>
    <x-slot:subheading>Configura las empresas institucionales y sus firmantes autorizados.</x-slot:subheading>

    @if (session('success'))
        <div class="mb-6 flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-900 shadow-sm" role="status">
            <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-emerald-100 text-emerald-700">
                <x-icon name="check" size="18" />
            </span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-900" role="alert">
            <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-red-100 text-red-700">
                <x-icon name="alert-circle" size="18" />
            </span>
            <div>
                <p class="font-extrabold">Se encontraron errores en el formulario:</p>
                <ul class="mt-2 list-disc pl-5 font-medium">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div data-tabs>
        <div class="mb-7 flex items-center justify-between gap-4 border-b border-slate-200 pb-2">
            <div class="flex gap-2 overflow-x-auto" role="tablist" aria-label="Secciones de empresas">
                <button type="button" id="tab-empresas" class="tab-button" role="tab" aria-selected="true" aria-controls="panel-empresas" data-tab="panel-empresas">
                    <x-icon name="building" size="17" /> Empresas Registradas <span class="tab-count">{{ $empresas->count() }}</span>
                </button>
                <button type="button" id="tab-firmantes" class="tab-button" role="tab" aria-selected="false" aria-controls="panel-firmantes" data-tab="panel-firmantes" tabindex="-1">
                    <x-icon name="award" size="17" /> Firmantes por Empresa <span class="tab-count">{{ $firmantes->count() }}</span>
                </button>
            </div>

            <a href="{{ route('empresas.create') }}" class="btn-primary shrink-0">
                <x-icon name="file-plus" size="18" /> Nueva Empresa
            </a>
        </div>

        {{-- TAB 1: EMPRESAS --}}
        <section id="panel-empresas" role="tabpanel" aria-labelledby="tab-empresas" data-tab-panel="panel-empresas">
            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($empresas as $empresa)
                    <div class="card flex flex-col justify-between overflow-hidden transition-all hover:shadow-md">
                        <div class="p-5 sm:p-6">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="grid size-12 shrink-0 place-items-center rounded-2xl bg-sdaya-50 text-sdaya-800 ring-1 ring-sdaya-200 overflow-hidden">
                                        @if ($empresa->logoDataUri())
                                            <img src="{{ $empresa->logoDataUri() }}" alt="{{ $empresa->nombre }}" class="size-full object-contain p-1">
                                        @else
                                            <x-icon name="building" size="22" />
                                        @endif
                                    </div>
                                    <div>
                                        <h3 class="font-extrabold text-slate-900 leading-snug">{{ $empresa->nombre }}</h3>
                                        <p class="text-xs text-slate-500 font-mono">NIT: {{ $empresa->nit ?: 'S/N' }}</p>
                                    </div>
                                </div>
                                <span class="status-pill {{ $empresa->activo ? 'status-active' : 'status-inactive' }}">
                                    {{ $empresa->activo ? 'Activa' : 'Inactiva' }}
                                </span>
                            </div>

                            <div class="mt-4 space-y-1.5 border-t border-slate-100 pt-3 text-xs text-slate-600">
                                @if ($empresa->direccion)
                                    <p class="flex items-center gap-2 truncate">
                                        <x-icon name="map-pin" size="14" class="text-slate-400 shrink-0" />
                                        <span>{{ $empresa->direccion }}</span>
                                    </p>
                                @endif
                                @if ($empresa->telefono)
                                    <p class="flex items-center gap-2">
                                        <x-icon name="phone" size="14" class="text-slate-400 shrink-0" />
                                        <span>{{ $empresa->telefono }}</span>
                                    </p>
                                @endif
                                @if ($empresa->correo)
                                    <p class="flex items-center gap-2 truncate">
                                        <x-icon name="mail" size="14" class="text-slate-400 shrink-0" />
                                        <span>{{ $empresa->correo }}</span>
                                    </p>
                                @endif
                                @if ($empresa->sitio_web)
                                    <p class="flex items-center gap-2 truncate">
                                        <x-icon name="globe" size="14" class="text-slate-400 shrink-0" />
                                        <a href="{{ $empresa->sitio_web }}" target="_blank" rel="noopener" class="text-sdaya-700 hover:underline">{{ $empresa->sitio_web }}</a>
                                    </p>
                                @endif
                            </div>

                            <div class="mt-4 flex items-center gap-4 text-xs font-bold text-slate-500 border-t border-slate-100 pt-3">
                                <span><strong class="text-slate-900">{{ $empresa->users_count }}</strong> firmantes</span>
                                <span>·</span>
                                <span><strong class="text-slate-900">{{ $empresa->documentos_count }}</strong> cartas</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between border-t border-slate-100 bg-slate-50/70 px-5 py-3 sm:px-6">
                            <a href="{{ route('empresas.edit', $empresa) }}" class="btn-ghost text-xs font-extrabold text-sdaya-700">
                                <x-icon name="edit" size="15" /> Editar
                            </a>

                            <form method="POST" action="{{ route('empresas.toggle', $empresa) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="{{ $empresa->activo ? 'btn-ghost text-red-700 hover:bg-red-50' : 'btn-secondary text-xs' }}" data-confirm="¿Deseas {{ $empresa->activo ? 'desactivar' : 'activar' }} la empresa '{{ $empresa->nombre }}'?">
                                    {{ $empresa->activo ? 'Desactivar' : 'Activar' }}
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="card p-8 text-center text-slate-500 md:col-span-2 xl:col-span-3">
                        <x-icon name="building" size="36" class="mx-auto text-slate-300" />
                        <p class="mt-2 font-bold text-slate-700">No hay empresas registradas.</p>
                        <p class="text-xs text-slate-400 mt-1">Crea la primera empresa para comenzar a emitir cartas institucionales.</p>
                    </div>
                @endforelse
            </div>
        </section>

        {{-- TAB 2: FIRMANTES POR EMPRESA --}}
        <section id="panel-firmantes" role="tabpanel" aria-labelledby="tab-firmantes" data-tab-panel="panel-firmantes">
            <div class="card overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 sm:px-6">
                    <div>
                        <h2 class="font-black text-sdaya-950">Firmantes Autorizados</h2>
                        <p class="text-xs text-slate-500">Usuarios con permisos de firma asignados a cada empresa institucional.</p>
                    </div>
                    <span class="rounded-full bg-sdaya-50 px-3 py-1 text-xs font-black text-sdaya-700 ring-1 ring-sdaya-200">
                        {{ $firmantes->count() }} firmantes
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="border-b border-slate-200 bg-slate-50 text-[11px] font-black uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-5 py-3 sm:px-6">Firmante</th>
                                <th class="px-5 py-3">Cargo</th>
                                <th class="px-5 py-3">Empresa Asignada</th>
                                <th class="px-5 py-3">Contacto</th>
                                <th class="px-5 py-3 text-right sm:px-6">Asignar Empresa</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            @forelse ($firmantes as $firmante)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-5 py-4 sm:px-6">
                                        <div class="flex items-center gap-3">
                                            <span class="grid size-9 place-items-center rounded-xl bg-sdaya-50 text-xs font-black text-sdaya-800 ring-1 ring-sdaya-200">
                                                {{ mb_strtoupper(mb_substr($firmante->name, 0, 2)) }}
                                            </span>
                                            <div>
                                                <p class="font-bold text-slate-900">{{ $firmante->name }}</p>
                                                <p class="text-xs text-slate-400">{{ $firmante->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="font-semibold text-slate-800">{{ $firmante->cargo ?: '— Sin cargo —' }}</span>
                                    </td>
                                    <td class="px-5 py-4">
                                        @if ($firmante->empresaInstitucion)
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-sdaya-50 px-2.5 py-1 text-xs font-bold text-sdaya-800 ring-1 ring-sdaya-200">
                                                <x-icon name="building" size="13" /> {{ $firmante->empresaInstitucion->nombre }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-800 ring-1 ring-amber-200">
                                                Sin empresa
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-xs">
                                        <p>{{ $firmante->telefono ?: 'Sin teléfono' }}</p>
                                        @if ($firmante->firma_digital)
                                            <span class="mt-1 inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700">
                                                <x-icon name="check" size="12" /> Firma cargada
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-right sm:px-6">
                                        <details class="inline-block text-left">
                                            <summary class="btn-ghost cursor-pointer text-xs font-bold text-sdaya-700">
                                                Cambiar empresa
                                            </summary>
                                            <div class="fixed inset-0 z-40 bg-black/20" onclick="this.parentElement.removeAttribute('open')"></div>
                                            <div class="absolute right-6 z-50 mt-2 w-72 rounded-2xl border border-slate-200 bg-white p-4 shadow-xl">
                                                <form method="POST" action="{{ route('empresas.asignar-firmante') }}">
                                                    @csrf
                                                    <input type="hidden" name="user_id" value="{{ $firmante->id }}">
                                                    <p class="font-extrabold text-xs text-slate-900 mb-2">Asignar {{ $firmante->name }} a:</p>
                                                    <div class="mb-3">
                                                        <select name="empresa_id" class="form-select text-xs" required>
                                                            @foreach ($empresas as $emp)
                                                                <option value="{{ $emp->id }}" @selected($firmante->empresa_id === $emp->id)>
                                                                    {{ $emp->nombre }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label text-[11px]">Cargo institucional</label>
                                                        <input type="text" name="cargo" value="{{ $firmante->cargo }}" class="form-input text-xs" placeholder="Ej. Gerente General">
                                                    </div>
                                                    <button type="submit" class="btn-primary w-full text-xs">Guardar Asignación</button>
                                                </form>
                                            </div>
                                        </details>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-slate-400">
                                        No hay usuarios firmantes registrados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>