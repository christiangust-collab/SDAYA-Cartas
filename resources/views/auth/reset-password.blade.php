<x-layouts.guest title="Nueva contraseña">
    <div class="flex items-center gap-3">
        <span class="grid size-11 place-items-center rounded-2xl bg-sdaya-50 text-sdaya-700 ring-1 ring-sdaya-200"><x-icon name="lock" size="21" /></span>
        <div>
            <p class="eyebrow text-sdaya-600">Acceso seguro</p>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-sdaya-950">Nueva contraseña</h1>
        </div>
    </div>
    <p class="mt-4 text-sm leading-6 text-slate-600">Usa una contraseña única de al menos ocho caracteres.</p>

    <form method="POST" action="{{ route('password.store') }}" class="mt-7 space-y-5" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="email" class="form-label">Correo electrónico</label>
            <input id="email" name="email" type="email" value="{{ old('email', $email) }}" class="form-input" autocomplete="email" required aria-describedby="email-error">
            <x-input-error id="email-error" :messages="$errors->get('email')" />
        </div>
        <div>
            <label for="password" class="form-label">Nueva contraseña</label>
            <div class="password-control">
                <input id="password" name="password" type="password" class="form-input" autocomplete="new-password" required aria-describedby="password-error">
                <button type="button" class="password-toggle" data-password-toggle aria-controls="password" aria-label="Mostrar contraseña" aria-pressed="false">
                    <x-icon name="eye" size="18" data-password-show />
                    <x-icon name="eye-off" size="18" class="hidden" data-password-hide />
                </button>
            </div>
            <x-input-error id="password-error" :messages="$errors->get('password')" />
        </div>
        <div>
            <label for="password_confirmation" class="form-label">Confirma la contraseña</label>
            <div class="password-control">
                <input id="password_confirmation" name="password_confirmation" type="password" class="form-input" autocomplete="new-password" required>
                <button type="button" class="password-toggle" data-password-toggle aria-controls="password_confirmation" aria-label="Mostrar contraseña" aria-pressed="false">
                    <x-icon name="eye" size="18" data-password-show />
                    <x-icon name="eye-off" size="18" class="hidden" data-password-hide />
                </button>
            </div>
        </div>
        <button type="submit" class="btn-primary w-full"><x-icon name="save" size="18" /> Guardar contraseña</button>
    </form>
</x-layouts.guest>
