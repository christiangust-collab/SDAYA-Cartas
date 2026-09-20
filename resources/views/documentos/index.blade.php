<x-layouts.app>
    <x-slot:title>Documentos</x-slot:title>
    <x-slot:heading>Documentos</x-slot:heading>
    <x-slot:subheading>Consulta, filtra y controla el ciclo de cada carta.</x-slot:subheading>
    @can('create', \App\Models\Documento::class)
        <x-slot:headerActions>
            <a href="{{ route('documentos.create') }}" class="btn-primary"><x-icon name="file-plus" size="17" /> <span class="hidden sm:inline">Nuevo documento</span><span class="sm:hidden">Nuevo</span></a>
        </x-slot:headerActions>
    @endcan

    <section aria-labelledby="resumen-titulo">
        <h1 id="resumen-titulo" class="sr-only">Resumen de documentos</h1>
        <dl class="grid grid-cols-2 gap-2.5 sm:gap-3 xl:grid-cols-4">
            @foreach ([
                ['Total', $resumen['total'], 'text-slate-800', 'bg-slate-100', 'ring-slate-200', 'file-text'],
                ['Borradores', $resumen['borradores'], 'text-amber-700', 'bg-amber-50/80', 'ring-amber-200', 'edit'],
                ['Emitidos', $resumen['emitidos'], 'text-emerald-700', 'bg-emerald-50/80', 'ring-emerald-200', 'check-circle'],
                ['Anulados', $resumen['anulados'], 'text-red-700', 'bg-red-50/80', 'ring-red-200', 'alert-circle'],
            ] as [$label, $value, $textColor, $bgColor, $ringColor, $icon])
                <div class="card flex items-center justify-between p-3 sm:p-3.5 transition-shadow hover:shadow-sm">
                    <div>
                        <dt class="text-[10px] sm:text-[11px] font-bold tracking-wide text-slate-500 uppercase">{{ $label }}</dt>
                        <dd class="mt-0.5 text-xl sm:text-2xl font-black {{ $textColor }} leading-tight">{{ number_format($value) }}</dd>
                    </div>
                    <span class="grid size-9 shrink-0 place-items-center rounded-xl {{ $bgColor }} {{ $textColor }} ring-1 {{ $ringColor }}">
                        <x-icon :name="$icon" size="17" />
                    </span>
                </div>
            @endforeach
        </dl>
    </section>

    <section class="card mt-4 p-3.5 sm:p-4" aria-labelledby="filtros-titulo">
        <div class="flex items-center justify-between gap-4 border-b border-slate-100 pb-2.5">
            <div class="flex items-center gap-2">
                <span class="grid size-7 place-items-center rounded-lg bg-slate-100 text-slate-700"><x-icon name="search" size="14" /></span>
                <div>
                    <h2 id="filtros-titulo" class="text-xs sm:text-sm font-black text-slate-900">Buscar y filtrar</h2>
                </div>
            </div>
            @if (array_filter($filtros))
                <a href="{{ route('documentos.index') }}" class="focus-ring rounded text-xs font-bold text-slate-600 hover:text-slate-900 hover:underline flex items-center gap-1">
                    <x-icon name="x" size="12" /> Limpiar filtros
                </a>
            @endif
        </div>
        <form method="GET" action="{{ route('documentos.index') }}" class="mt-3 grid gap-2.5 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-[1.4fr_repeat(5,1fr)_auto]">
            <div>
                <label for="buscar" class="sr-only">Texto</label>
                <input id="buscar" name="buscar" type="search" value="{{ $filtros['buscar'] ?? '' }}" class="form-input text-xs py-2" placeholder="Buscar CITE, destinatario o referencia (ej. EPSAS)...">
            </div>
            <div>
                <label for="empresa_id" class="sr-only">Empresa</label>
                <select id="empresa_id" name="empresa_id" class="form-select text-xs py-2">
                    <option value="">Empresa: Todas</option>
                    @foreach ($empresas as $empresa)
                        <option value="{{ $empresa->id }}" @selected((string) ($filtros['empresa_id'] ?? '') === (string) $empresa->id)>{{ $empresa->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="area_id" class="sr-only">Área</label>
                <select id="area_id" name="area_id" class="form-select text-xs py-2">
                    <option value="">Área: Todas</option>
                    @foreach ($areas as $area)
                        <option value="{{ $area->id }}" @selected((string) ($filtros['area_id'] ?? '') === (string) $area->id)>{{ $area->codigo }} - {{ $area->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="tipo_id" class="sr-only">Tipo</label>
                <select id="tipo_id" name="tipo_id" class="form-select text-xs py-2">
                    <option value="">Tipo: Todos</option>
                    @foreach ($tipos as $tipo)
                        <option value="{{ $tipo->id }}" @selected((string) ($filtros['tipo_id'] ?? '') === (string) $tipo->id)>{{ $tipo->codigo }} - {{ $tipo->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="anio" class="sr-only">Año</label>
                <select id="anio" name="anio" class="form-select text-xs py-2">
                    <option value="">Año: Todos</option>
                    @foreach ($anios as $anio)
                        <option value="{{ $anio }}" @selected((string) ($filtros['anio'] ?? '') === (string) $anio)>{{ $anio }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="estado" class="sr-only">Estado</label>
                <select id="estado" name="estado" class="form-select text-xs py-2">
                    <option value="">Estado: Todos</option>
                    @foreach ($estados as $estado)
                        <option value="{{ $estado->value }}" @selected(($filtros['estado'] ?? '') === $estado->value)>{{ $estado->etiqueta() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center">
                <button type="submit" class="btn-secondary w-full py-2 text-xs font-bold xl:w-auto"><x-icon name="search" size="14" /> Filtrar</button>
            </div>
        </form>
    </section>

    <section class="card mt-4 overflow-hidden" aria-labelledby="listado-titulo">
        <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-5 py-4">
            <div><h2 id="listado-titulo" class="font-black text-slate-900">Listado de documentos</h2><p class="mt-1 text-xs text-slate-500">Ordenados por la actualización más reciente.</p></div>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-700 ring-1 ring-slate-200">{{ $documentos->total() }} resultado{{ $documentos->total() === 1 ? '' : 's' }}</span>
        </div>

        @if ($documentos->isEmpty())
            <div class="px-5 py-16 text-center">
                <span class="mx-auto grid size-12 place-items-center rounded-2xl bg-slate-100 text-slate-400"><x-icon name="documents" size="22" /></span>
                <p class="mt-4 text-lg font-extrabold text-slate-900">No encontramos documentos.</p>
                <p class="mt-2 text-sm text-slate-500">Prueba otros filtros o crea un nuevo borrador.</p>
            </div>
        @else
            <div class="hidden overflow-x-auto lg:block">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">Documentos registrados en el sistema</caption>
                    <thead class="border-b border-slate-200 bg-slate-50/80 text-[10px] tracking-[0.12em] text-slate-500 uppercase">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-bold">CITE / Borrador</th>
                            <th scope="col" class="px-5 py-3 font-bold">Destinatario</th>
                            <th scope="col" class="px-5 py-3 font-bold">Referencia</th>
                            <th scope="col" class="px-5 py-3 font-bold">Clasificación</th>
                            <th scope="col" class="px-5 py-3 font-bold">Estado</th>
                            <th scope="col" class="px-5 py-3 font-bold">Actualizado</th>
                            <th scope="col" class="px-5 py-3 text-right font-bold">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($documentos as $documento)
                            <tr class="group transition-colors hover:bg-slate-50">
                                <td class="px-5 py-4 whitespace-nowrap"><span class="font-mono font-bold text-slate-900">{{ $documento->cite ?: 'BORRADOR #'.$documento->id }}</span><br><span class="text-xs text-slate-500">{{ $documento->fecha_documento->format('d/m/Y') }}</span></td>
                                <td class="max-w-xs px-5 py-4">
                                    @if (filled($documento->destinatario))
                                        <div class="flex items-start gap-1.5">
                                            <span class="mt-0.5 shrink-0 text-slate-400"><x-icon name="user" size="14" /></span>
                                            <p class="font-bold text-slate-900 text-xs leading-snug line-clamp-2" title="{{ $documento->destinatario }}">{{ $documento->destinatario }}</p>
                                        </div>
                                    @else
                                        <span class="text-xs italic text-slate-400">Sin destinatario</span>
                                    @endif
                                </td>
                                <td class="max-w-xs px-5 py-4">
                                    <p class="font-bold text-slate-950 text-xs leading-snug line-clamp-2" title="{{ $documento->asunto }}">
                                        {{ $documento->asunto ?: \Illuminate\Support\Str::limit(trim(strip_tags($documento->contenido)), 70) }}
                                    </p>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="font-bold">{{ $documento->area->codigo }}</span> · {{ $documento->tipo->codigo }}
                                    <p class="mt-0.5 text-[11px] font-bold text-slate-500 truncate max-w-40" title="{{ $documento->datosEmpresa()['nombre'] }}">{{ $documento->datosEmpresa()['nombre'] }}</p>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap"><x-estado-badge :estado="$documento->estado" /></td>
                                <td class="px-5 py-4 text-slate-600 text-xs whitespace-nowrap">{{ $documento->updated_at->diffForHumans() }}</td>
                                <td class="px-5 py-4 text-right whitespace-nowrap">
                                    <a href="{{ route('documentos.show', $documento) }}" class="focus-ring inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 font-extrabold transition-colors hover:bg-slate-100 {{ $documento->estaBorrador() ? 'text-amber-800 hover:text-amber-950' : 'text-slate-700 hover:text-slate-950' }}">
                                        @if ($documento->estaBorrador())
                                            <x-icon name="eye" size="15" /> Ver borrador
                                        @else
                                            Ver detalle <x-icon name="arrow-right" size="15" />
                                        @endif
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <ul class="divide-y divide-slate-100 lg:hidden">
                @foreach ($documentos as $documento)
                    <li>
                        <a href="{{ route('documentos.show', $documento) }}" class="focus-ring block p-5 transition-colors hover:bg-slate-50">
                            <div class="flex items-start justify-between gap-3">
                                <span class="font-mono text-sm font-extrabold text-slate-900">{{ $documento->cite ?: 'BORRADOR #'.$documento->id }}</span>
                                <x-estado-badge :estado="$documento->estado" />
                            </div>
                            @if (filled($documento->destinatario))
                                <p class="mt-2 text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                    <x-icon name="user" size="13" class="text-slate-400 shrink-0" />
                                    <span class="truncate">Para: {{ $documento->destinatario }}</span>
                                </p>
                            @endif
                            <p class="mt-2 font-bold text-slate-950 text-sm">{{ $documento->asunto ?: \Illuminate\Support\Str::limit(trim(strip_tags($documento->contenido)), 80) }}</p>
                            <div class="mt-3 flex items-center justify-between gap-3">
                                <p class="text-sm text-slate-500">{{ $documento->area->codigo }} · {{ $documento->tipo->codigo }} · {{ $documento->fecha_documento->format('d/m/Y') }}</p>
                                <span class="inline-flex items-center gap-1 text-xs font-bold {{ $documento->estaBorrador() ? 'text-amber-800' : 'text-slate-700' }}">
                                    {{ $documento->estaBorrador() ? 'Ver borrador' : 'Ver detalle' }}
                                    <x-icon name="{{ $documento->estaBorrador() ? 'eye' : 'arrow-right' }}" size="16" />
                                </span>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="border-t border-slate-200 px-5 py-4">{{ $documentos->links() }}</div>
        @endif
    </section>
</x-layouts.app>
