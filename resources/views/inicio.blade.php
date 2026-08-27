<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Gestión documental segura, trazable y verificable para SDAYA.">
    <title>SDAYA Cartas | Gestión documental verificable</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white font-sans text-slate-900 antialiased">
    <a href="#contenido" class="skip-link">Saltar al contenido principal</a>

    <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/90 backdrop-blur-xl">
        <div class="mx-auto flex min-h-20 max-w-7xl items-center justify-between gap-6 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('inicio') }}" class="focus-ring rounded-xl" aria-label="Inicio de SDAYA Cartas"><x-application-logo /></a>
            <nav aria-label="Navegación pública" class="flex items-center gap-1 sm:gap-2">
                <a href="#proceso" class="public-nav-link hidden md:inline-flex">Cómo funciona</a>
                <a href="#verificar" class="public-nav-link hidden sm:inline-flex">Verificar</a>
                @auth
                    <a href="{{ route('panel') }}" class="btn-primary"><x-icon name="arrow-right" size="17" /> Ir al panel</a>
                @else
                    <a href="{{ route('login') }}" class="btn-primary"><x-icon name="lock" size="17" /> <span class="hidden sm:inline">Acceso interno</span><span class="sm:hidden">Ingresar</span></a>
                @endauth
            </nav>
        </div>
    </header>

    <main id="contenido" tabindex="-1">
        <section class="sdaya-grid relative overflow-hidden border-b border-slate-200/80">
            <div class="pointer-events-none absolute top-16 left-[48%] size-[34rem] rounded-full border border-sdaya-300/20" aria-hidden="true"></div>
            <div class="pointer-events-none absolute top-32 left-[53%] size-[26rem] rounded-full border border-sdaya-300/25" aria-hidden="true"></div>

            <div class="relative mx-auto grid max-w-7xl gap-14 px-4 py-16 sm:px-6 sm:py-20 lg:grid-cols-[1.06fr_0.94fr] lg:items-center lg:px-8 lg:py-28">
                <div class="reveal">
                    <div class="flex items-center gap-3">
                        <span class="h-px w-10 bg-sdaya-500"></span>
                        <p class="eyebrow text-sdaya-600">Documentación institucional</p>
                    </div>
                    <h1 class="mt-6 max-w-3xl text-4xl leading-[1.06] font-black tracking-[-0.045em] text-sdaya-950 sm:text-6xl">
                        Gestión documental <span class="text-sdaya-600">segura, trazable</span> y verificable.
                    </h1>
                    <p class="mt-6 max-w-2xl text-base leading-8 text-slate-600 sm:text-lg">
                        SDAYA Cartas organiza la emisión de documentos oficiales, asigna un CITE único y permite comprobar su autenticidad mediante QR.
                    </p>
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a href="#verificar" class="btn-primary"><x-icon name="shield-check" size="18" /> Verificar un documento</a>
                        <a href="{{ route('login') }}" class="btn-secondary">Ingresar al sistema <x-icon name="arrow-right" size="18" /></a>
                    </div>
                    <div class="mt-9 flex flex-wrap gap-2.5" aria-label="Características principales">
                        <span class="trust-chip"><x-icon name="lock" size="15" class="text-sdaya-600" /> Archivos privados</span>
                        <span class="trust-chip"><x-icon name="history" size="15" class="text-sdaya-600" /> Historial auditable</span>
                        <span class="trust-chip"><x-icon name="qr" size="15" class="text-sdaya-600" /> Validación por QR</span>
                    </div>
                </div>

                <div class="relative mx-auto w-full max-w-lg reveal reveal-delay-2">
                    <div class="absolute -inset-8 -z-10 rounded-[3rem] bg-gradient-to-br from-sdaya-400/25 via-sdaya-500/10 to-transparent blur-3xl" aria-hidden="true"></div>
                    <article class="document-float overflow-hidden rounded-[1.75rem] border border-sdaya-200/80 bg-white shadow-2xl shadow-sdaya-950/15" aria-label="Ejemplo visual de un documento verificable">
                        <div class="h-2 bg-gradient-to-r from-sdaya-600 via-sdaya-500 to-sdaya-400"></div>
                        <div class="p-6 sm:p-8">
                            <div class="flex items-start justify-between gap-5 border-b border-slate-200 pb-6">
                                <div class="rounded-xl bg-white p-1"><x-application-logo compact /></div>
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-[10px] font-black tracking-wider text-emerald-800 uppercase"><span class="size-1.5 rounded-full bg-emerald-600"></span>Emitido</span>
                            </div>

                            <div class="py-7">
                                <p class="detail-term">Código institucional</p>
                                <p class="mt-2 font-mono text-xl font-black tracking-tight text-sdaya-950 sm:text-2xl">SD-ADM-NE-2026/001</p>
                                <div class="mt-7 space-y-3" aria-hidden="true">
                                    <span class="block h-2 w-11/12 rounded-full bg-slate-200"></span>
                                    <span class="block h-2 w-full rounded-full bg-slate-100"></span>
                                    <span class="block h-2 w-4/5 rounded-full bg-slate-100"></span>
                                </div>
                            </div>

                            <div class="flex items-end justify-between gap-5 border-t border-slate-200 pt-6">
                                <div>
                                    <p class="detail-term">Integridad</p>
                                    <p class="mt-2 inline-flex items-center gap-2 text-sm font-extrabold text-emerald-700"><x-icon name="check-circle" size="18" /> Comprobada</p>
                                    <p class="mt-2 text-xs text-slate-500">Datos y PDF protegidos</p>
                                </div>
                                <div class="grid size-24 place-items-center rounded-2xl border border-slate-200 bg-slate-50 text-sdaya-950 shadow-inner">
                                    <x-icon name="qr" size="58" stroke-width="1.45" />
                                </div>
                            </div>
                        </div>
                    </article>

                    <div class="absolute -right-2 -bottom-5 flex items-center gap-3 rounded-2xl border border-white bg-sdaya-950 px-4 py-3 text-white shadow-xl sm:-right-8" aria-hidden="true">
                        <span class="grid size-9 place-items-center rounded-xl bg-sdaya-400/20 text-sdaya-300"><x-icon name="shield-check" size="20" /></span>
                        <span><span class="block text-[10px] font-bold tracking-wider text-sdaya-300 uppercase">Autenticidad</span><span class="mt-0.5 block text-xs font-black">Verificada por SDAYA</span></span>
                    </div>
                </div>
            </div>
        </section>

        <section id="verificar" class="verification-panel scroll-mt-20 overflow-hidden py-16 text-white sm:py-20">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-[0.82fr_1.18fr] lg:items-center lg:px-8">
                <div class="max-w-xl">
                    <span class="grid size-12 place-items-center rounded-2xl bg-white/10 text-sdaya-300 ring-1 ring-white/15"><x-icon name="shield-check" size="24" /></span>
                    <p class="eyebrow mt-6 text-sdaya-300">Consulta pública</p>
                    <h2 class="mt-3 text-3xl leading-tight font-black tracking-[-0.03em] sm:text-4xl">Confirma un documento en segundos.</h2>
                    <p class="mt-4 leading-7 text-sdaya-100">Utiliza el código impreso junto al QR. La consulta permite verificar la autenticidad, el estado y la integridad de la carta, además de visualizar su información y contenido.</p>
                </div>

                <form method="POST" action="{{ route('verificar.buscar') }}" class="rounded-3xl bg-white p-5 text-slate-900 shadow-2xl shadow-black/20 sm:p-8" novalidate>
                    @csrf
                    <div class="flex items-start gap-3">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-sdaya-50 text-sdaya-700"><x-icon name="search" size="20" /></span>
                        <div>
                            <label for="codigo" class="block font-black text-sdaya-950">Código de verificación</label>
                            <p id="codigo-ayuda" class="mt-1 text-xs leading-5 text-slate-500">Pega los 64 caracteres que aparecen en el documento.</p>
                        </div>
                    </div>
                    <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                        <input id="codigo" name="codigo" type="text" value="{{ old('codigo') }}" class="form-input font-mono text-sm" placeholder="Ej.: a9f3…" maxlength="64" spellcheck="false" autocomplete="off" required aria-describedby="codigo-ayuda codigo-error">
                        <button type="submit" class="btn-primary shrink-0">Verificar ahora <x-icon name="arrow-right" size="17" /></button>
                    </div>
                    <x-input-error id="codigo-error" :messages="$errors->get('codigo')" />
                    <div class="mt-5 flex items-center gap-2 border-t border-slate-200 pt-4 text-xs font-semibold text-slate-500">
                        <x-icon name="lock" size="15" class="text-sdaya-600" /> En esta consulta puedes visualizar los datos y el contenido de la carta.
                    </div>
                </form>
            </div>
        </section>

        <section id="proceso" class="scroll-mt-24 bg-white py-16 sm:py-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="max-w-2xl">
                    <p class="eyebrow text-sdaya-600">Flujo documental</p>
                    <h2 class="mt-3 text-3xl font-black tracking-[-0.03em] text-sdaya-950 sm:text-4xl">Del borrador a la confianza pública.</h2>
                    <p class="mt-4 leading-7 text-slate-600">Un proceso simple para el equipo y verificable para cualquier destinatario.</p>
                </div>

                <ol class="relative mt-12 grid gap-5 md:grid-cols-3">
                    @foreach ([
                        ['01', 'file-text', 'Crear', 'El equipo prepara el documento sin consumir todavía un número correlativo.'],
                        ['02', 'send', 'Emitir', 'El sistema asigna el CITE definitivo y genera Word, PDF y QR.'],
                        ['03', 'shield-check', 'Verificar', 'El destinatario confirma públicamente el estado y la integridad del archivo.'],
                    ] as [$numero, $icono, $titulo, $descripcion])
                        <li class="group relative overflow-hidden rounded-3xl border border-slate-200 bg-slate-50/70 p-6 transition-[border-color,transform,box-shadow] duration-300 hover:-translate-y-1 hover:border-sdaya-300 hover:shadow-xl hover:shadow-sdaya-950/6 sm:p-7">
                            <span class="absolute top-4 right-5 font-mono text-4xl font-black text-sdaya-100" aria-hidden="true">{{ $numero }}</span>
                            <span class="grid size-12 place-items-center rounded-2xl bg-white text-sdaya-700 shadow-sm ring-1 ring-sdaya-200"><x-icon :name="$icono" size="23" /></span>
                            <h3 class="mt-6 text-xl font-black text-sdaya-950">{{ $titulo }}</h3>
                            <p class="mt-3 text-sm leading-6 text-slate-600">{{ $descripcion }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>

        <section class="border-y border-slate-200 bg-sdaya-50/70">
            <div class="mx-auto grid max-w-7xl divide-y divide-sdaya-200 px-4 sm:px-6 md:grid-cols-3 md:divide-x md:divide-y-0 lg:px-8">
                @foreach ([
                    ['lock', 'Gestión y consulta de documentos', 'El sistema permite consultar y visualizar la información y el contenido de las cartas.'],
                    ['history', 'Trazabilidad completa', 'Cada creación, actualización, emisión y anulación queda registrada.'],
                    ['check-circle', 'Integridad comprobable', 'La plataforma valida los datos y el archivo PDF asociado.'],
                ] as [$icono, $titulo, $descripcion])
                    <article class="flex gap-4 px-2 py-8 md:px-7 md:py-10 first:pl-0 last:pr-0">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-white text-sdaya-700 shadow-sm ring-1 ring-sdaya-200"><x-icon :name="$icono" size="19" /></span>
                        <div><h3 class="text-sm font-black text-sdaya-950">{{ $titulo }}</h3><p class="mt-1.5 text-xs leading-5 text-slate-600">{{ $descripcion }}</p></div>
                    </article>
                @endforeach
            </div>
        </section>
    </main>

    <footer class="bg-white">
        <div class="mx-auto flex max-w-7xl flex-col gap-5 px-4 py-8 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
            <div class="flex items-center gap-3"><x-application-logo compact /><span class="hidden h-7 w-px bg-slate-200 sm:block"></span><span class="hidden text-xs font-semibold text-slate-500 sm:block">Gestión documental institucional</span></div>
            <p class="text-xs text-slate-500">© {{ now()->year }} SDAYA. Todos los derechos reservados.</p>
        </div>
    </footer>
</body>
</html>
