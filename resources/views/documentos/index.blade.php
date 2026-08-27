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
        <dl class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['Total', $resumen['total'], 'text-sdaya-700'],
                ['Borradores', $resumen['borradores'], 'text-amber-700'],
                ['Emitidos', $resumen['emitidos'], 'text-emerald-700'],
                ['Anulados', $resumen['anulados'], 'text-red-700'],
            ] as [$label, $value, $color])
                <div class="card p-5">
                    <dt class="text-xs font-bold tracking-wide text-slate-500 uppercase">{{ $label }}</dt>
                    <dd class="mt-2 text-3xl font-black {{ $color }}">{{ number_format($value) }}</dd>
                </div>
            @endforeach
        </dl>
    </section>

    <section class="card mt-6 p-4 sm:p-5" aria-labelledby="filtros-titulo">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-3"><span class="grid size-9 place-items-center rounded-xl bg-sdaya-50 text-sdaya-700"><x-icon name="search" size="17" /></span><div><h2 id="filtros-titulo" class="text-base font-black text-sdaya-950">Buscar y filtrar</h2><p class="mt-0.5 hidden text-xs text-slate-500 sm:block">Encuentra documentos por sus datos principales.</p></div></div>
            @if (array_filter($filtros))
                <a href="{{ route('documentos.index') }}" class="focus-ring rounded text-sm font-bold text-sdaya-700 hover:underline">Limpiar filtros</a>
            @endif
        </div>
        <form method="GET" action="{{ route('documentos.index') }}" class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-[1.6fr_repeat(4,1fr)_auto]">
            <div>
                <label for="buscar" class="form-label">Texto</label>
                <input id="buscar" name="buscar" type="search" value="{{ $filtros['buscar'] ?? '' }}" class="form-input" placeholder="CITE, asunto o destinatario">
            </div>
            <div>
                <label for="area_id" class="form-label">Área</label>
                <select id="area_id" name="area_id" class="form-select">
                    <option value="">Todas</option>
                    @foreach ($areas as $area)
                        <option value="{{ $area->id }}" @selected((string) ($filtros['area_id'] ?? '') === (string) $area->id)>{{ $area->codigo }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="tipo_id" class="form-label">Tipo</label>
                <select id="tipo_id" name="tipo_id" class="form-select">
                    <option value="">Todos</option>
                    @foreach ($tipos as $tipo)
                        <option value="{{ $tipo->id }}" @selected((string) ($filtros['tipo_id'] ?? '') === (string) $tipo->id)>{{ $tipo->codigo }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="anio" class="form-label">Año</label>
                <select id="anio" name="anio" class="form-select">
                    <option value="">Todos</option>
                    @foreach ($anios as $anio)
                        <option value="{{ $anio }}" @selected((string) ($filtros['anio'] ?? '') === (string) $anio)>{{ $anio }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="estado" class="form-label">Estado</label>
                <select id="estado" name="estado" class="form-select">
                    <option value="">Todos</option>
                    @foreach ($estados as $estado)
                        <option value="{{ $estado->value }}" @selected(($filtros['estado'] ?? '') === $estado->value)>{{ $estado->etiqueta() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn-secondary w-full xl:w-auto"><x-icon name="search" size="17" /> Aplicar</button>
            </div>
        </form>
    </section>

    <section class="card mt-6 overflow-hidden" aria-labelledby="listado-titulo">
        <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-5 py-4">
            <div><h2 id="listado-titulo" class="font-black text-sdaya-950">Listado de documentos</h2><p class="mt-1 text-xs text-slate-500">Ordenados por la actualización más reciente.</p></div>
            <span class="rounded-full bg-sdaya-50 px-3 py-1 text-xs font-black text-sdaya-700 ring-1 ring-sdaya-200">{{ $documentos->total() }} resultado{{ $documentos->total() === 1 ? '' : 's' }}</span>
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
                            <th scope="col" class="px-5 py-3 font-bold">CITE / borrador</th>
                            <th scope="col" class="px-5 py-3 font-bold">Documento</th>
                            <th scope="col" class="px-5 py-3 font-bold">Clasificación</th>
                            <th scope="col" class="px-5 py-3 font-bold">Estado</th>
                            <th scope="col" class="px-5 py-3 font-bold">Actualizado</th>
                            <th scope="col" class="px-5 py-3 text-right font-bold">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($documentos as $documento)
                            <tr class="group transition-colors hover:bg-sdaya-50/60">
                                <td class="px-5 py-4"><span class="font-mono font-bold text-sdaya-800">{{ $documento->cite ?: 'BORRADOR #'.$documento->id }}</span><br><span class="text-xs text-slate-500">{{ $documento->fecha_documento->format('d/m/Y') }}</span></td>
                                <td class="max-w-sm px-5 py-4"><p class="truncate font-bold text-slate-950">{{ $documento->asunto ?: \Illuminate\Support\Str::limit(trim(strip_tags($documento->contenido)), 80) }}</p>@if (filled($documento->destinatario))<p class="mt-1 truncate text-xs text-slate-500">Para: {{ $documento->destinatario }}</p>@endif</td>
                                <td class="px-5 py-4"><span class="font-bold">{{ $documento->area->codigo }}</span> · {{ $documento->tipo->codigo }}</td>
                                <td class="px-5 py-4"><x-estado-badge :estado="$documento->estado" /></td>
                                <td class="px-5 py-4 text-slate-600">{{ $documento->updated_at->diffForHumans() }}</td>
                                <td class="px-5 py-4 text-right"><a href="{{ route('documentos.show', $documento) }}" class="focus-ring inline-flex items-center gap-1 rounded-lg px-2 py-1.5 font-extrabold text-sdaya-700 transition-colors hover:bg-white hover:text-sdaya-900">Ver detalle <x-icon name="arrow-right" size="15" /></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <ul class="divide-y divide-slate-100 lg:hidden">
                @foreach ($documentos as $documento)
                    <li>
                        <a href="{{ route('documentos.show', $documento) }}" class="focus-ring block p-5 transition-colors hover:bg-sdaya-50">
                            <div class="flex items-start justify-between gap-3"><span class="font-mono text-sm font-extrabold text-sdaya-800">{{ $documento->cite ?: 'BORRADOR #'.$documento->id }}</span><x-estado-badge :estado="$documento->estado" /></div>
                            <p class="mt-3 font-bold text-slate-950">{{ $documento->asunto ?: \Illuminate\Support\Str::limit(trim(strip_tags($documento->contenido)), 80) }}</p>
                            <div class="mt-3 flex items-center justify-between gap-3"><p class="text-sm text-slate-500">{{ $documento->area->codigo }} · {{ $documento->tipo->codigo }} · {{ $documento->fecha_documento->format('d/m/Y') }}</p><x-icon name="arrow-right" size="17" class="text-sdaya-600" /></div>
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="border-t border-slate-200 px-5 py-4">{{ $documentos->links() }}</div>
        @endif
    </section>
</x-layouts.app>
