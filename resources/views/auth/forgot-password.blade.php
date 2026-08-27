<x-layouts.guest title="Recuperar contraseña">
    <div class="flex items-center gap-3">
        <span class="grid size-11 place-items-center rounded-2xl bg-sdaya-50 text-sdaya-700 ring-1 ring-sdaya-200"><x-icon name="mail" size="21" /></span>
        <div>
            <p class="eyebrow text-sdaya-600">Recuperación de acceso</p>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-sdaya-950">Recupera tu cuenta</h1>
        </div>
    </div>
    <p class="mt-4 text-sm leading-6 text-slate-600">Te enviaremos un enlace seguro al correo asociado con tu cuenta.</p>

    <x-flash-messages />

    <form method="POST" action="{{ route('password.email') }}" class="mt-7 space-y-5" novalidate>
        @csrf
        <div>
            <label for="email" class="form-label">Correo electrónico</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" class="form-input" autocomplete="email" required autofocus aria-describedby="email-error">
            <x-input-error id="email-error" :messages="$errors->get('email')" />
        </div>
        <button type="submit" class="btn-primary w-full"><x-icon name="send" size="18" /> Enviar enlace</button>
        <a href="{{ route('login') }}" class="btn-secondary w-full"><x-icon name="arrow-left" size="18" /> Volver al inicio de sesión</a>
    </form>
</x-layouts.guest>
