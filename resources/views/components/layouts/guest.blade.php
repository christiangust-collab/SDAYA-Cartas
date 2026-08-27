@props(['title' => 'Acceso'])

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Acceso seguro a SDAYA Cartas.">
    <title>{{ $title ?? 'Acceso' }} | SDAYA Cartas</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white font-sans text-slate-900 antialiased">
    <a href="#contenido" class="skip-link">Saltar al contenido principal</a>

    <main id="contenido" class="grid min-h-screen lg:grid-cols-[minmax(25rem,0.92fr)_minmax(32rem,1.08fr)]" tabindex="-1">
        <section class="guest-brand-panel relative hidden min-h-screen overflow-hidden px-10 py-9 text-white lg:flex lg:flex-col lg:justify-between xl:px-16 xl:py-12" aria-label="Presentación de SDAYA Cartas">
            <a href="{{ route('inicio') }}" class="focus-ring relative z-10 w-fit rounded-xl bg-white px-3 py-2.5" aria-label="Volver al inicio de SDAYA Cartas">
                <x-application-logo />
            </a>

            <div class="relative z-10 max-w-xl py-12">
                <div class="flex items-center gap-3">
                    <span class="h-px w-10 bg-sdaya-400"></span>
                    <p class="eyebrow text-sdaya-300">Plataforma institucional</p>
                </div>
                <h1 class="mt-6 text-4xl leading-[1.08] font-black tracking-[-0.035em] text-white xl:text-5xl">
                    Cada documento,<br><span class="text-sdaya-300">una identidad verificable.</span>
                </h1>
                <p class="mt-6 max-w-lg text-base leading-7 text-sdaya-100">
                    Emisión segura, trazabilidad completa y validación pública mediante QR en una sola plataforma.
                </p>

                <ul class="mt-9 grid gap-3 text-sm font-bold text-sdaya-50 sm:grid-cols-2">
                    <li class="flex items-center gap-2.5"><span class="grid size-7 place-items-center rounded-lg bg-white/10 text-sdaya-300"><x-icon name="check" size="15" /></span>CITE automático</li>
                    <li class="flex items-center gap-2.5"><span class="grid size-7 place-items-center rounded-lg bg-white/10 text-sdaya-300"><x-icon name="check" size="15" /></span>Historial auditable</li>
                    <li class="flex items-center gap-2.5"><span class="grid size-7 place-items-center rounded-lg bg-white/10 text-sdaya-300"><x-icon name="check" size="15" /></span>Archivos privados</li>
                    <li class="flex items-center gap-2.5"><span class="grid size-7 place-items-center rounded-lg bg-white/10 text-sdaya-300"><x-icon name="check" size="15" /></span>Verificación pública</li>
                </ul>
            </div>

            <p class="relative z-10 text-xs leading-5 text-sdaya-300">© {{ now()->year }} SDAYA · Sistemas, Desarrollo de Aplicaciones y Auditoría</p>
        </section>

        <section class="sdaya-grid flex min-h-screen items-center justify-center px-4 py-8 sm:px-8 lg:bg-white lg:px-12 xl:px-20">
            <div class="w-full max-w-[31rem] reveal">
                <div class="mb-8 flex items-center justify-between lg:hidden">
                    <a href="{{ route('inicio') }}" class="focus-ring rounded-xl bg-white px-2.5 py-2 shadow-sm" aria-label="Volver al inicio de SDAYA Cartas">
                        <x-application-logo compact />
                    </a>
                    <span class="rounded-full border border-sdaya-200 bg-white px-3 py-1.5 text-[10px] font-black tracking-wider text-sdaya-700 uppercase">Acceso seguro</span>
                </div>

                <a href="{{ route('inicio') }}" class="focus-ring mb-7 hidden w-fit items-center gap-2 rounded-lg text-sm font-extrabold text-slate-500 hover:text-sdaya-800 lg:inline-flex">
                    <x-icon name="arrow-left" size="17" /> Volver al sitio público
                </a>

                <section class="rounded-3xl border border-slate-200/90 bg-white p-6 shadow-xl shadow-sdaya-950/8 sm:p-9">
                    {{ $slot }}
                </section>

                <p class="mt-6 text-center text-xs leading-5 text-slate-500">
                    Acceso exclusivo para personal autorizado de SDAYA.
                </p>
            </div>
        </section>
    </main>
</body>
</html>
