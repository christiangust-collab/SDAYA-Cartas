<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Gestión institucional de documentos de {{ $empresaActual->nombre }}.">
    <title>{{ $title ?? 'Panel' }} | {{ $empresaActual->nombre_aplicacion ?? config('app.name', 'Gestión de Cartas') }}</title>
    @if ($empresaActual->faviconDataUri())
        <link rel="icon" href="{{ $empresaActual->faviconDataUri() }}">
    @endif
    <script>
        try {
            if (window.localStorage.getItem('sdaya-sidebar') === 'collapsed') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        } catch (error) {}
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @php
        $colorPri = $empresaActual->color_principal ?: '#172944';
        $colorSec = $empresaActual->color_secundario ?: '#4766a9';

        $hexSec = ltrim($colorSec, '#');
        if (strlen($hexSec) === 3) {
            $hexSec = $hexSec[0].$hexSec[0].$hexSec[1].$hexSec[1].$hexSec[2].$hexSec[2];
        }
        $r = hexdec(substr($hexSec, 0, 2) ?: '47');
        $g = hexdec(substr($hexSec, 2, 2) ?: '66');
        $b = hexdec(substr($hexSec, 4, 2) ?: 'a9');
        $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
        $colorTextBtn = ($yiq >= 160) ? '#0f172a' : '#ffffff';
    @endphp
    <style>
        :root {
            --brand-primary: {{ $colorPri }};
            --brand-secondary: {{ $colorSec }};
            --brand-btn-text: {{ $colorTextBtn }};
        }
        #sidebar {
            background-color: var(--brand-primary) !important;
        }
        .btn-primary {
            background-color: var(--brand-secondary) !important;
            border-color: var(--brand-secondary) !important;
            color: var(--brand-btn-text) !important;
        }
        .btn-primary:hover {
            filter: brightness(1.08);
        }
        .nav-link-active::before {
            background-color: var(--brand-secondary) !important;
        }
        .tab-button[aria-selected='true'] {
            border-color: var(--brand-secondary) !important;
            color: var(--brand-secondary) !important;
        }
    </style>
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
            <div class="flex min-h-20 items-center justify-between border-b border-white/10 px-4 py-3">
                <a href="{{ route('documentos.index') }}" class="focus-ring flex min-w-0 flex-1 items-center gap-3 rounded-xl" aria-label="Ir a documentos">
                    <span class="sidebar-logo-full flex h-11 max-w-[140px] items-center justify-center rounded-xl bg-white/95 px-2 py-1 shadow-sm overflow-hidden shrink-0">
                        <x-application-logo compact />
                    </span>
                    <span class="sidebar-monogram hidden size-10 place-items-center rounded-xl bg-white text-xs font-black tracking-tight text-slate-900 shadow-sm">{{ $empresaActual->monograma() }}</span>
                    <span class="sidebar-brand-copy min-w-0 flex-1">
                        <span class="block text-xs font-black tracking-wide text-white leading-tight break-words">{{ $empresaActual->nombre_aplicacion ?? 'Gestión de Cartas' }}</span>
                        <span class="mt-0.5 inline-block rounded bg-white/15 px-1.5 py-0.5 text-[9px] font-bold tracking-wider text-slate-200 uppercase truncate max-w-full">{{ $empresaActual->nombre_comercial ?: \Illuminate\Support\Str::limit($empresaActual->nombre, 16) }}</span>
                    </span>
                </a>

                <button type="button" class="focus-ring hidden size-8 shrink-0 place-items-center rounded-lg text-white/70 transition-colors hover:bg-white/10 hover:text-white lg:grid" data-sidebar-toggle aria-label="Contraer navegación" aria-pressed="false">
                    <x-icon name="panel-close" size="16" class="sidebar-collapse-icon" />
                    <x-icon name="panel-open" size="16" class="sidebar-expand-icon" />
                </button>

                <button type="button" class="focus-ring grid size-9 shrink-0 place-items-center rounded-lg text-white/80 hover:bg-white/10 lg:hidden" data-menu-close aria-label="Cerrar menú">
                    <x-icon name="x" size="20" />
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
                    <a href="{{ route('perfil.edit') }}" aria-label="Mi Perfil y Firma" title="Mi Perfil y Firma" @if(request()->routeIs('perfil.*')) aria-current="page" @endif @class(['nav-link', 'nav-link-active' => request()->routeIs('perfil.*')])>
                        <x-icon name="award" />
                        <span class="sidebar-label">Mi Perfil / Firma</span>
                    </a>
                </div>

                @can('viewAny', \App\Models\Area::class)
                    <p class="nav-section-label sidebar-label">Administración</p>
                    <div class="space-y-1">
                        <a href="{{ route('empresas.index') }}" aria-label="Empresas" title="Empresas" @if(request()->routeIs('empresas.*')) aria-current="page" @endif @class(['nav-link', 'nav-link-active' => request()->routeIs('empresas.*')])>
                            <x-icon name="building" />
                            <span class="sidebar-label">Empresas</span>
                        </a>
                        <a href="{{ route('catalogos.index') }}" aria-label="Catálogos" title="Catálogos" @if(request()->routeIs('catalogos.*')) aria-current="page" @endif @class(['nav-link', 'nav-link-active' => request()->routeIs('catalogos.*')])>
                            <x-icon name="layers" />
                            <span class="sidebar-label">Catálogos</span>
                        </a>
                        <a href="{{ route('usuarios.index') }}" aria-label="Usuarios" title="Gestión de Usuarios y Cuentas" @if(request()->routeIs('usuarios.*')) aria-current="page" @endif @class(['nav-link', 'nav-link-active' => request()->routeIs('usuarios.*')])>
                            <x-icon name="users" />
                            <span class="sidebar-label">Usuarios</span>
                        </a>
                        <a href="{{ route('ajustes.index') }}" aria-label="Ajustes" title="Ajustes de Empresa e Identidad" @if(request()->routeIs('ajustes.*')) aria-current="page" @endif @class(['nav-link', 'nav-link-active' => request()->routeIs('ajustes.*')])>
                            <x-icon name="settings" />
                            <span class="sidebar-label">Ajustes</span>
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
                        <p class="hidden text-[10px] font-black tracking-[0.16em] text-sdaya-600 uppercase sm:block">Panel interno · {{ $empresaActual->nombre_aplicacion ?? 'Gestión de Cartas' }}</p>
                        <p class="truncate text-base font-black tracking-tight text-sdaya-950">{{ $heading ?? ($title ?? ($empresaActual->nombre_aplicacion ?? 'Gestión de Cartas')) }}</p>
                        @isset($subheading)
                            <p class="hidden truncate text-xs text-slate-500 xl:block">{{ $subheading }}</p>
                        @endisset
                    </div>
                </div>

                <div class="ml-4 flex shrink-0 items-center gap-3">
                    @isset($headerActions)
                        <div class="flex items-center gap-2">{{ $headerActions }}</div>
                    @endisset

                    <details class="relative">
                        <summary class="focus-ring flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200/80 bg-white p-1 pr-2.5 transition-colors hover:border-slate-300 hover:bg-slate-50 list-none [&::-webkit-details-marker]:hidden" aria-label="Menú de usuario" title="Menú de usuario">
                            <span class="grid size-8 place-items-center rounded-lg bg-sdaya-50 text-xs font-black text-sdaya-800 ring-1 ring-sdaya-200">{{ $iniciales }}</span>
                            <div class="hidden text-left sm:block">
                                <p class="max-w-28 truncate text-xs font-black text-slate-800 leading-tight">{{ $usuario->name }}</p>
                                <p class="text-[10px] font-semibold text-slate-400">{{ $usuario->role->etiqueta() }}</p>
                            </div>
                            <x-icon name="arrow-right" size="12" class="text-slate-400 rotate-90 transition-transform hidden sm:block" />
                        </summary>

                        <div class="fixed inset-0 z-40 bg-black/5" onclick="this.parentElement.removeAttribute('open')"></div>

                        <div class="absolute right-0 z-50 mt-2 w-64 origin-top-right rounded-2xl border border-slate-200 bg-white p-2 shadow-xl shadow-slate-950/10">
                            <div class="border-b border-slate-100 p-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="grid size-10 place-items-center rounded-xl bg-sdaya-50 text-sm font-black text-sdaya-800 ring-1 ring-sdaya-200">
                                        {{ $iniciales }}
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-xs font-black text-slate-900">{{ $usuario->name }}</p>
                                        <p class="truncate text-[11px] text-slate-400 font-medium">{{ $usuario->email }}</p>
                                        <span class="mt-1 inline-flex items-center rounded-full bg-sdaya-50 px-2 py-0.5 text-[10px] font-bold text-sdaya-700 ring-1 ring-sdaya-200">
                                            {{ $usuario->role->etiqueta() }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-1 py-1.5">
                                <a href="{{ route('perfil.edit') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 transition-colors hover:bg-slate-100 hover:text-sdaya-900">
                                    <x-icon name="user" size="15" class="text-slate-400" /> Mi Perfil / Firma
                                </a>

                                @can('viewAny', \App\Models\Area::class)
                                    <a href="{{ route('usuarios.index') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 transition-colors hover:bg-slate-100 hover:text-sdaya-900">
                                        <x-icon name="users" size="15" class="text-slate-400" /> Gestión de Usuarios
                                    </a>
                                    <a href="{{ route('ajustes.index') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 transition-colors hover:bg-slate-100 hover:text-sdaya-900">
                                        <x-icon name="settings" size="15" class="text-slate-400" /> Ajustes de Empresa
                                    </a>
                                @endcan
                            </div>

                            <div class="border-t border-slate-100 pt-1">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-left text-xs font-bold text-red-600 transition-colors hover:bg-red-50 hover:text-red-700">
                                        <x-icon name="log-out" size="15" class="text-red-500" /> Cerrar sesión / Cambiar cuenta
                                    </button>
                                </form>
                            </div>
                        </div>
                    </details>
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
