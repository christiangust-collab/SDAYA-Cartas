<x-layouts.guest title="Iniciar sesión">
    <div class="flex items-center gap-3">
        <span class="grid size-11 place-items-center rounded-2xl bg-sdaya-50 text-sdaya-700 ring-1 ring-sdaya-200"><x-icon name="lock" size="21" /></span>
        <div>
            <p class="eyebrow text-sdaya-600">Acceso interno</p>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-sdaya-950">Bienvenido de nuevo</h1>
        </div>
    </div>
    <p class="mt-4 text-sm leading-6 text-slate-600">Ingresa con tu cuenta institucional para gestionar documentos.</p>

    <x-flash-messages />

    <form method="POST" action="{{ route('login.store') }}" class="mt-7 space-y-5" novalidate>
        @csrf
        <div>
            <label for="email" class="form-label">Correo electrónico</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" class="form-input" autocomplete="username" required autofocus aria-describedby="email-error">
            <x-input-error id="email-error" :messages="$errors->get('email')" />
        </div>

        <div>
            <div class="flex items-center justify-between gap-4">
                <label for="password" class="form-label">Contraseña</label>
                <a href="{{ route('password.request') }}" class="focus-ring rounded text-xs font-bold text-sdaya-700 hover:underline">¿La olvidaste?</a>
            </div>
            <div class="password-control">
                <input id="password" name="password" type="password" class="form-input" autocomplete="current-password" required aria-describedby="password-error">
                <button type="button" class="password-toggle" data-password-toggle aria-controls="password" aria-label="Mostrar contraseña" aria-pressed="false">
                    <x-icon name="eye" size="18" data-password-show />
                    <x-icon name="eye-off" size="18" class="hidden" data-password-hide />
                </button>
            </div>
            <x-input-error id="password-error" :messages="$errors->get('password')" />
        </div>

        <label class="flex items-center gap-3 text-sm text-slate-600">
            <input type="checkbox" name="remember" value="1" class="size-4 rounded border-slate-300 text-sdaya-600 focus:ring-sdaya-500">
            Mantener mi sesión iniciada
        </label>

        <button type="submit" class="btn-primary w-full"><x-icon name="arrow-right" size="18" /> Ingresar al sistema</button>
    </form>
</x-layouts.guest>
