<x-layouts.app>
    <x-slot:title>Ajustes de Empresa e Identidad</x-slot:title>
    <x-slot:heading>Configuración de Empresa</x-slot:heading>
    <x-slot:subheading>Personaliza la identidad institucional, colores corporativos y apariencia del sistema.</x-slot:subheading>

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

    <form method="POST" action="{{ route('ajustes.update') }}" enctype="multipart/form-data" novalidate>
        @csrf
        @method('PUT')

        @if ($empresa->id)
            <input type="hidden" name="empresa_activa_id" value="{{ $empresa->id }}">
        @endif

        <div data-tabs>
            <div class="mb-7 flex items-center justify-between gap-4 border-b border-slate-200 pb-2">
                <div class="flex gap-2 overflow-x-auto" role="tablist" aria-label="Secciones de ajustes">
                    <button type="button" id="tab-empresa" class="tab-button" role="tab" aria-selected="true" aria-controls="panel-empresa" data-tab="panel-empresa">
                        <x-icon name="building" size="17" /> Datos de Empresa
                    </button>
                    <button type="button" id="tab-visual" class="tab-button" role="tab" aria-selected="false" aria-controls="panel-visual" data-tab="panel-visual" tabindex="-1">
                        <x-icon name="award" size="17" /> Identidad Visual y Colores
                    </button>
                    <button type="button" id="tab-docs" class="tab-button" role="tab" aria-selected="false" aria-controls="panel-docs" data-tab="panel-docs" tabindex="-1">
                        <x-icon name="file-text" size="17" /> Membrete / Documentos
                    </button>
                </div>

                <button type="submit" class="btn-primary shrink-0">
                    <x-icon name="save" size="18" /> Guardar Ajustes
                </button>
            </div>

            {{-- TAB 1: DATOS INSTITUCIONALES --}}
            <section id="panel-empresa" role="tabpanel" aria-labelledby="tab-empresa" data-tab-panel="panel-empresa">
                <div class="grid gap-6 lg:grid-cols-3">
                    <div class="lg:col-span-2 space-y-6">
                        <div class="card p-6 sm:p-8">
                            <h2 class="text-base font-black text-slate-900 border-b border-slate-100 pb-3 mb-5">
                                Información Institucional
                            </h2>
                            <div class="grid gap-5 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <label for="nombre" class="form-label">Razón Social / Nombre Oficial <span class="text-red-500">*</span></label>
                                    <input type="text" id="nombre" name="nombre" value="{{ old('nombre', $empresa->nombre) }}" class="form-input" required placeholder="Ej. Constructora ABC S.R.L.">
                                </div>

                                <div>
                                    <label for="nombre_comercial" class="form-label">Nombre Comercial o Sigla (Prefijo CITE)</label>
                                    <input type="text" id="nombre_comercial" name="nombre_comercial" value="{{ old('nombre_comercial', $empresa->nombre_comercial) }}" class="form-input" placeholder="Ej. CCNC, SDAYA, ABC">
                                    <p class="field-note"><x-icon name="info" size="14" /> Sigla que se usará como prefijo para los CITEs oficiales (ej. <strong>{{ $empresa->siglaCite() }}</strong>-CNT-NE-2026/001).</p>
                                </div>

                                <div>
                                    <label for="nit" class="form-label">NIT o Identificación Tributaria</label>
                                    <input type="text" id="nit" name="nit" value="{{ old('nit', $empresa->nit) }}" class="form-input font-mono" placeholder="Ej. 1029384756">
                                </div>

                                <div class="sm:col-span-2">
                                    <label for="direccion" class="form-label">Dirección Institucional</label>
                                    <input type="text" id="direccion" name="direccion" value="{{ old('direccion', $empresa->direccion) }}" class="form-input" placeholder="Ej. Av. 16 de Julio #1490, Edif. Parque del Parque, Piso 5">
                                </div>

                                <div>
                                    <label for="telefono" class="form-label">Teléfono o Celular</label>
                                    <input type="text" id="telefono" name="telefono" value="{{ old('telefono', $empresa->telefono) }}" class="form-input" placeholder="Ej. +591 2 2441234">
                                </div>

                                <div>
                                    <label for="correo" class="form-label">Correo Electrónico de Contacto</label>
                                    <input type="email" id="correo" name="correo" value="{{ old('correo', $empresa->correo) }}" class="form-input" placeholder="Ej. contacto@empresa.com">
                                </div>

                                <div class="sm:col-span-2">
                                    <label for="sitio_web" class="form-label">Sitio Web Oficial</label>
                                    <input type="url" id="sitio_web" name="sitio_web" value="{{ old('sitio_web', $empresa->sitio_web) }}" class="form-input" placeholder="Ej. https://www.empresa.com">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="card p-6 bg-slate-50/70 border-slate-200">
                            <h3 class="text-sm font-black text-slate-900 mb-2">Empresa Activa del Sistema</h3>
                            <p class="text-xs text-slate-500 leading-5 mb-4">
                                Esta empresa define la identidad institucional principal de esta instalación. Las cartas que se emitan tomarán estos datos por defecto.
                            </p>

                            @if ($todasEmpresas->count() > 1)
                                <label for="selector_empresa_activa" class="form-label text-xs">Cambiar a otra empresa registrada:</label>
                                <select id="selector_empresa_activa" class="form-select text-xs" onchange="if(this.value){ window.location.href = '{{ route('ajustes.index') }}?empresa_id=' + this.value; }">
                                    @foreach ($todasEmpresas as $item)
                                        <option value="{{ $item->id }}" @selected($item->id === $empresa->id)>
                                            {{ $item->nombre }} {{ $item->es_predeterminada ? '(Predeterminada)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif

                            <div class="mt-4 pt-4 border-t border-slate-200 text-xs text-slate-500">
                                <span class="font-bold text-slate-700">Nota histórica:</span> Las cartas emitidas anteriormente preservan sus datos institucionales intactos sin verse alteradas.
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- TAB 2: IDENTIDAD VISUAL Y COLORES --}}
            <section id="panel-visual" role="tabpanel" aria-labelledby="tab-visual" data-tab-panel="panel-visual">
                <div class="grid gap-6 lg:grid-cols-3">
                    <div class="lg:col-span-2 space-y-6">
                        <div class="card p-6 sm:p-8">
                            <h2 class="text-base font-black text-slate-900 border-b border-slate-100 pb-3 mb-5">
                                Nombre de la Aplicación y Marca
                            </h2>

                            <div class="space-y-5">
                                <div>
                                    <label for="nombre_aplicacion" class="form-label">Nombre Visible de la Aplicación <span class="text-red-500">*</span></label>
                                    <input type="text" id="nombre_aplicacion" name="nombre_aplicacion" value="{{ old('nombre_aplicacion', $empresa->nombre_aplicacion ?: 'Gestión de Cartas') }}" class="form-input" required placeholder="Ej. XYZ Cartas o Gestión Documental ABC">
                                    <p class="field-note"><x-icon name="info" size="14" /> Aparece en la barra superior, pestaña del navegador y correos institucionales.</p>
                                </div>

                                <div class="grid gap-5 sm:grid-cols-2 pt-4 border-t border-slate-100">
                                    <div>
                                        <label for="color_principal" class="form-label">Color Principal (Sidebar y Fondos)</label>
                                        <div class="flex items-center gap-3">
                                            <input type="color" id="picker_principal" value="{{ old('color_principal', $empresa->color_principal ?: '#002b49') }}" class="size-11 cursor-pointer rounded-xl border border-slate-300 p-1 bg-white" oninput="document.getElementById('color_principal').value = this.value; actualizarPreview();">
                                            <input type="text" id="color_principal" name="color_principal" value="{{ old('color_principal', $empresa->color_principal ?: '#002b49') }}" class="form-input font-mono uppercase" maxlength="7" pattern="^#(?:[0-9a-fA-F]{3}){1,2}$" oninput="if(/^#(?:[0-9a-fA-F]{3}){1,2}$/.test(this.value)){ document.getElementById('picker_principal').value = this.value; actualizarPreview(); }">
                                        </div>
                                        <p class="field-note"><x-icon name="info" size="14" /> Usado para el panel lateral de navegación.</p>
                                    </div>

                                    <div>
                                        <label for="color_secundario" class="form-label">Color Secundario (Botones e Interacción)</label>
                                        <div class="flex items-center gap-3">
                                            <input type="color" id="picker_secundario" value="{{ old('color_secundario', $empresa->color_secundario ?: '#00487a') }}" class="size-11 cursor-pointer rounded-xl border border-slate-300 p-1 bg-white" oninput="document.getElementById('color_secundario').value = this.value; actualizarPreview();">
                                            <input type="text" id="color_secundario" name="color_secundario" value="{{ old('color_secundario', $empresa->color_secundario ?: '#00487a') }}" class="form-input font-mono uppercase" maxlength="7" pattern="^#(?:[0-9a-fA-F]{3}){1,2}$" oninput="if(/^#(?:[0-9a-fA-F]{3}){1,2}$/.test(this.value)){ document.getElementById('picker_secundario').value = this.value; actualizarPreview(); }">
                                        </div>
                                        <p class="field-note"><x-icon name="info" size="14" /> Usado para botones principales y enlaces destacados.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card p-6 sm:p-8">
                            <h2 class="text-base font-black text-slate-900 border-b border-slate-100 pb-3 mb-5">
                                Logotipo y Favicon
                            </h2>

                            <div class="grid gap-6 sm:grid-cols-2">
                                <div>
                                    <label class="form-label">Logo Principal del Sistema</label>
                                    @if ($empresa->logoDataUri())
                                        <div class="mb-3 flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                            <img src="{{ $empresa->logoDataUri() }}" alt="Logo actual" class="h-10 max-w-32 object-contain">
                                            <label class="ml-auto inline-flex items-center gap-2 text-xs font-bold text-red-600 hover:text-red-800 cursor-pointer">
                                                <input type="checkbox" name="eliminar_logo" value="1" class="rounded border-slate-300"> Eliminar
                                            </label>
                                        </div>
                                    @endif
                                    <input type="file" name="logo" class="form-input text-xs" accept="image/png,image/jpeg,image/webp,image/svg+xml">
                                    <p class="field-note"><x-icon name="info" size="14" /> Recomendado: PNG transparente o SVG. Cuadrado (512×512 px) o apaisado (600×180 px), recortado al ras del dibujo sin márgenes blancos vacíos.</p>
                                </div>

                                <div>
                                    <label class="form-label">Favicon (Pestaña del Navegador)</label>
                                    @if ($empresa->faviconDataUri())
                                        <div class="mb-3 flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                            <img src="{{ $empresa->faviconDataUri() }}" alt="Favicon actual" class="size-6 object-contain">
                                            <label class="ml-auto inline-flex items-center gap-2 text-xs font-bold text-red-600 hover:text-red-800 cursor-pointer">
                                                <input type="checkbox" name="eliminar_favicon" value="1" class="rounded border-slate-300"> Eliminar
                                            </label>
                                        </div>
                                    @endif
                                    <input type="file" name="favicon" class="form-input text-xs" accept="image/png,image/x-icon,image/svg+xml,image/webp">
                                    <p class="field-note"><x-icon name="info" size="14" /> PNG, ICO o SVG cuadrado (máx. 1MB).</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="card p-6 bg-slate-50/70 border-slate-200 sticky top-24">
                            <h3 class="text-sm font-black text-slate-900 mb-3">Previsualización en Vivo</h3>
                            <div class="rounded-2xl border border-slate-200 overflow-hidden shadow-sm bg-white">
                                <div id="preview-sidebar" class="p-4 text-white flex items-center gap-3" style="background-color: {{ $empresa->color_principal ?: '#002b49' }};">
                                    <div class="size-8 rounded-lg bg-white/20 grid place-items-center text-xs font-black">
                                        {{ $empresa->monograma() }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div id="preview-app-name" class="font-black text-xs truncate">
                                            {{ $empresa->nombre_aplicacion ?: 'Gestión de Cartas' }}
                                        </div>
                                        <div class="text-[10px] text-white/70 truncate">
                                            {{ $empresa->nombre_comercial ?: $empresa->nombre }}
                                        </div>
                                    </div>
                                </div>
                                <div class="p-4 space-y-3">
                                    <button type="button" id="preview-button" class="w-full text-center text-xs font-black text-white py-2 px-3 rounded-xl shadow-sm" style="background-color: {{ $empresa->color_secundario ?: '#00487a' }};">
                                        Botón Principal
                                    </button>
                                    <p class="text-[11px] text-slate-500 text-center">Así se verán los controles y el panel lateral con tus colores.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- TAB 3: DOCUMENTOS Y MEMBRETE --}}
            <section id="panel-docs" role="tabpanel" aria-labelledby="tab-docs" data-tab-panel="panel-docs">
                <div class="card p-6 sm:p-8 max-w-3xl">
                    <h2 class="text-base font-black text-slate-900 border-b border-slate-100 pb-3 mb-5">
                        Logo y Membrete para Documentos Oficiales
                    </h2>

                    <div class="space-y-6">
                        <p class="text-sm text-slate-600 leading-6">
                            Puedes configurar un membrete o diseño de página exclusivo para las cartas impresas en formato PDF y exportadas a Word (.docx). Si no configuras ninguno o lo eliminas, los documentos se emitirán en hoja en blanco limpia.
                        </p>

                        <div>
                            <label class="form-label">Membrete / Logo para Cartas (PDF y Word)</label>
                            @if ($empresa->logo_documentos && $empresa->logoDocumentosDataUri())
                                <div class="mb-3 flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <img src="{{ $empresa->logoDocumentosDataUri() }}" alt="Membrete de cartas" class="max-h-16 max-w-48 object-contain">
                                    <label class="ml-auto inline-flex items-center gap-2 text-xs font-bold text-red-600 hover:text-red-800 cursor-pointer">
                                        <input type="checkbox" name="eliminar_logo_documentos" value="1" class="rounded border-slate-300"> Eliminar membrete
                                    </label>
                                </div>
                            @endif
                            <input type="file" name="logo_documentos" class="form-input text-xs" accept="image/png,image/jpeg,image/webp,image/svg+xml,application/pdf">
                            <p class="field-note"><x-icon name="info" size="14" /> Formato PNG, JPG o <strong>PDF</strong> (máx. 5MB). Si subes un PDF, el sistema convertirá automáticamente la página 1 a imagen de alta resolución para tus documentos Word y PDF.</p>
                        </div>

                        <div class="rounded-2xl border border-blue-100 bg-blue-50/60 p-4 text-xs text-blue-900 flex items-start gap-3">
                            <span class="grid size-6 place-items-center rounded-lg bg-blue-200 text-blue-800 shrink-0">
                                <x-icon name="shield-check" size="14" />
                            </span>
                            <div>
                                <p class="font-bold">Garantía de inmutabilidad histórica</p>
                                <p class="mt-1 text-blue-800/90 leading-5">
                                    Cambiar el membrete o logotipo solo afectará a las nuevas cartas que se creen o emitan a partir de ahora. Todas las cartas previamente emitidas conservan su copia y membrete histórico sin modificaciones.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </form>

    <script>
        function actualizarPreview() {
            const pri = document.getElementById('color_principal').value;
            const sec = document.getElementById('color_secundario').value;
            const appName = document.getElementById('nombre_aplicacion').value;

            const sb = document.getElementById('preview-sidebar');
            const btn = document.getElementById('preview-button');
            const title = document.getElementById('preview-app-name');

            if (sb && pri) sb.style.backgroundColor = pri;
            if (btn && sec) btn.style.backgroundColor = sec;
            if (title && appName) title.textContent = appName;
        }

        document.getElementById('nombre_aplicacion')?.addEventListener('input', actualizarPreview);
    </script>
</x-layouts.app>