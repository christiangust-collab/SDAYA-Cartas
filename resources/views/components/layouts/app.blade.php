<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Gestión institucional de documentos SDAYA.">
    <title>{{ $title ?? 'Panel' }} | SDAYA Cartas</title>
    <script>
        try {
            if (window.localStorage.getItem('sdaya-sidebar') === 'collapsed') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        } catch (error) {}
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f5f7fb] font-sans text-slate-900 antialiased">
    <a href="#contenido" class="skip-link">Saltar al contenido principal</a>

    @php
        $usuario = auth()->user();
        $iniciales = collect(preg_split('/\s+/', trim($usuario->name)))
            ->filter()
            ->take(2)
            ->map(fn ($parte) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($parte, 0, 1)))
            ->implode('');
    @endphp

    <div class="app-shell">
        <div id="mobile-overlay" class="fixed inset-0 z-30 hidden bg-sdaya-950/60 backdrop-blur-sm lg:hidden" data-menu-close aria-hidden="true"></div>

        <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col overflow-hidden border-r border-white/10 bg-sdaya-950 text-white shadow-2xl shadow-sdaya-950/20 transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen lg:w-full lg:translate-x-0 lg:shadow-none" aria-label="Navegación principal">
            <div class="flex min-h-22 items-center gap-2 border-b border-white/10 px-4">
                <a href="{{ route('documentos.index') }}" class="focus-ring flex min-w-0 flex-1 items-center gap-3 rounded-xl" aria-label="Ir a documentos">
                    <span class="sidebar-logo-full grid h-11 min-w-16 place-items-center rounded-xl bg-white px-2 shadow-sm">
                        <x-application-logo compact />
                    </span>
                    <span class="sidebar-monogram hidden size-11 place-items-center rounded-xl bg-white text-sm font-black tracking-tight text-sdaya-900 shadow-sm">SD</span>
                    <span class="sidebar-brand-copy min-w-0">
                        <span class="block text-sm font-black tracking-wide text-white">SDAYA Cartas</span>
                        <span class="mt-0.5 block truncate text-[10px] font-bold tracking-[0.12em] text-sdaya-300 uppercase">Gestión documental</span>
                    </span>
                </a>

                <button type="button" class="focus-ring hidden size-9 shrink-0 place-items-center rounded-lg text-sdaya-300 transition-colors hover:bg-white/10 hover:text-white lg:grid" data-sidebar-toggle aria-label="Contraer navegación" aria-pressed="false">
                    <x-icon name="panel-close" size="18" class="sidebar-collapse-icon" />
                    <x-icon name="panel-open" size="18" class="sidebar-expand-icon" />
                </button>

                <button type="button" class="focus-ring grid size-10 shrink-0 place-items-center rounded-lg text-sdaya-100 hover:bg-white/10 lg:hidden" data-menu-close aria-label="Cerrar menú">
                    <x-icon name="x" size="22" />
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 py-4">
                <p class="nav-section-label sidebar-label">Espacio de trabajo</p>
                <div class="space-y-1">
                    <a href="{{ route('documentos.index') }}" aria-label="Documentos" title="Documentos" @if(request()->routeIs('documentos.index', 'documentos.show')) aria-current="page" @endif @class(['nav-link', 'nav-link-active' => request()->routeIs('documentos.index', 'documentos.show')])>
                        <x-icon name="documents" />
                        <span class="sidebar-label">Documentos</span>
                    </a>
                    @can('create', \App\Models\Documento::class)
                        <a href="{{ route('documentos.create') }}" aria-label="Nuevo documento" title="Nuevo documento" @if(request()->routeIs('documentos.create', 'documentos.edit')) aria-current="page" @endif @class(['nav-link', 'nav-link-active' => request()->routeIs('documentos.create', 'documentos.edit')])>
                            <x-icon name="file-plus" />
                            <span class="sidebar-label">Nuevo documento</span>
                        </a>
                    @endcan
                </div>

                @can('viewAny', \App\Models\Area::class)
                    <p class="nav-section-label sidebar-label">Administración</p>
                    <div class="space-y-1">
                        <a href="{{ route('catalogos.index') }}" aria-label="Catálogos" title="Catálogos" @if(request()->routeIs('catalogos.*')) aria-current="page" @endif @class(['nav-link', 'nav-link-active' => request()->routeIs('catalogos.*')])>
                            <x-icon name="layers" />
                            <span class="sidebar-label">Catálogos</span>
                        </a>
                    </div>
                @endcan

                <p class="nav-section-label sidebar-label">Consulta externa</p>
                <div class="space-y-1">
                    <a href="{{ route('inicio') }}#verificar" class="nav-link" aria-label="Verificación pública" title="Verificación pública" target="_blank" rel="noopener">
                        <x-icon name="shield-check" />
                        <span class="sidebar-label">Verificación pública</span>
                    </a>
                </div>
            </nav>

            <div class="border-t border-white/10 p-3">
                <div class="sidebar-user flex items-center gap-3 rounded-xl bg-white/6 px-3 py-3">
                    <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-sdaya-400/20 text-xs font-black text-sdaya-200 ring-1 ring-sdaya-400/25">{{ $iniciales }}</span>
                    <div class="sidebar-user-copy min-w-0">
                        <p class="truncate text-xs font-extrabold text-white">{{ $usuario->name }}</p>
                        <p class="mt-0.5 text-[10px] font-bold tracking-wide text-sdaya-300 uppercase">{{ $usuario->role->etiqueta() }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button type="submit" class="sidebar-logout focus-ring flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-sdaya-200 transition-colors hover:bg-white/10 hover:text-white" title="Cerrar sesión">
                        <x-icon name="log-out" />
                        <span class="sidebar-label">Cerrar sesión</span>
                    </button>
                </form>
            </div>
        </aside>

        <div class="min-w-0">
            <header class="sticky top-0 z-20 flex min-h-18 items-center justify-between border-b border-slate-200/90 bg-white/92 px-4 backdrop-blur-xl sm:px-6 lg:px-8">
                <div class="flex min-w-0 items-center gap-3">
                    <button type="button" class="icon-button lg:hidden" data-menu-open aria-controls="sidebar" aria-expanded="false" aria-label="Abrir menú">
                        <x-icon name="menu" size="21" />
                    </button>
                    <div class="min-w-0">
                        <p class="hidden text-[10px] font-black tracking-[0.16em] text-sdaya-600 uppercase sm:block">Panel interno · SDAYA Cartas</p>
                        <p class="truncate text-base font-black tracking-tight text-sdaya-950">{{ $heading ?? ($title ?? 'SDAYA Cartas') }}</p>
                        @isset($subheading)
                            <p class="hidden truncate text-xs text-slate-500 xl:block">{{ $subheading }}</p>
                        @endisset
                    </div>
                </div>

                <div class="ml-4 flex shrink-0 items-center gap-2">
                    @isset($headerActions)
                        <div class="flex items-center gap-2">{{ $headerActions }}</div>
                    @endisset
                    <div class="hidden items-center gap-2 border-l border-slate-200 pl-3 md:flex" aria-label="Usuario actual">
                        <span class="grid size-9 place-items-center rounded-xl bg-sdaya-50 text-xs font-black text-sdaya-800 ring-1 ring-sdaya-200">{{ $iniciales }}</span>
                        <div class="hidden max-w-36 lg:block">
                            <p class="truncate text-xs font-extrabold text-slate-800">{{ $usuario->name }}</p>
                            <p class="text-[10px] font-bold text-slate-500">{{ $usuario->role->etiqueta() }}</p>
                        </div>
                    </div>
                </div>
            </header>

            <main id="contenido" class="mx-auto w-full max-w-[96rem] px-4 py-6 sm:px-6 lg:px-8 lg:py-8" tabindex="-1">
                <x-flash-messages />
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
