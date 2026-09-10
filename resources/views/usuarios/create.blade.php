<x-layouts.app>
    <x-slot:title>Nuevo Usuario</x-slot:title>
    <x-slot:heading>Registrar Nuevo Usuario</x-slot:heading>
    <x-slot:subheading>Crea una nueva cuenta institucional para el acceso al sistema, asignando su rol y empresa.</x-slot:subheading>

    <div class="mb-6">
        <a href="{{ route('usuarios.index') }}" class="btn-ghost inline-flex items-center gap-2 text-xs font-extrabold text-sdaya-700">
            <x-icon name="arrow-left" size="16" /> Volver al listado de usuarios
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-6 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-900" role="alert">
            <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-red-100 text-red-700">
                <x-icon name="alert-circle" size="18" />
            </span>
            <div>
                <p class="font-extrabold">Por favor corrige los siguientes errores:</p>
                <ul class="mt-2 list-disc pl-5 font-medium">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('usuarios.store') }}" class="card max-w-3xl overflow-hidden" novalidate>
        @csrf

        <div class="border-b border-sdaya-100 bg-sdaya-50 p-5 sm:p-6">
            <div class="flex items-center gap-3">
                <span class="grid size-10 place-items-center rounded-xl bg-white text-sdaya-700 shadow-sm ring-1 ring-sdaya-200">
                    <x-icon name="user-plus" size="20" />
                </span>
                <div>
                    <h2 class="text-lg font-black text-sdaya-950">Datos de la Cuenta</h2>
                    <p class="text-xs text-slate-500">Ingresa la información personal y credenciales de acceso para el nuevo usuario.</p>
                </div>
            </div>
        </div>

        <div class="space-y-6 p-5 sm:p-8">
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="name" class="form-label">Nombre Completo <span class="text-red-500">*</span></label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" class="form-input" maxlength="150" placeholder="Ej. Lic. María Flores Mamani" required>
                    <p class="field-note"><x-icon name="info" size="14" /> Nombre que aparecerá en el sistema y en los pies de firma.</p>
                </div>

                <div>
                    <label for="email" class="form-label">Correo Institucional <span class="text-red-500">*</span></label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" class="form-input" maxlength="150" placeholder="usuario@sdaya.com.bo" required>
                    <p class="field-note"><x-icon name="mail" size="14" /> Se usará para iniciar sesión.</p>
                </div>

                <div>
                    <label for="empresa_id" class="form-label">Empresa Asignada <span class="text-red-500">*</span></label>
                    <select id="empresa_id" name="empresa_id" class="form-input" required>
                        <option value="">-- Seleccionar Empresa --</option>
                        @foreach ($empresas as $emp)
                            <option value="{{ $emp->id }}" @selected(old('empresa_id') == $emp->id)>
                                {{ $emp->nombre }}
                            </option>
                        @endforeach
                    </select>
                    <p class="field-note"><x-icon name="building" size="14" /> Entidad institucional a la que pertenece.</p>
                </div>

                <div>
                    <label for="cargo" class="form-label">Cargo Institucional</label>
                    <input id="cargo" name="cargo" type="text" value="{{ old('cargo') }}" class="form-input" maxlength="150" placeholder="Ej. Responsable de Correspondencia">
                </div>

                <div>
                    <label for="telefono" class="form-label">Teléfono / Celular</label>
                    <input id="telefono" name="telefono" type="text" value="{{ old('telefono') }}" class="form-input" maxlength="50" placeholder="Ej. +591 70012345">
                </div>
            </div>

            {{-- SELECTOR DE ROL CON TARJETAS EXPLICATIVAS --}}
            <div class="border-t border-slate-100 pt-5">
                <label class="form-label mb-3 block">Rol en el Sistema <span class="text-red-500">*</span></label>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($roles as $r)
                        <label class="relative flex cursor-pointer flex-col rounded-2xl border p-4 shadow-sm transition hover:border-sdaya-400 has-[:checked]:border-sdaya-600 has-[:checked]:bg-sdaya-50/50 has-[:checked]:ring-2 has-[:checked]:ring-sdaya-500/20">
                            <input type="radio" name="role" value="{{ $r->value }}" @checked(old('role', \App\Enums\RolUsuario::EDITOR->value) === $r->value) class="sr-only">
                            <div class="flex items-center justify-between">
                                <span class="font-black text-slate-900 text-sm">{{ $r->etiqueta() }}</span>
                                <span class="size-2 rounded-full {{ $r === \App\Enums\RolUsuario::ADMIN ? 'bg-indigo-500' : 'bg-emerald-500' }}"></span>
                            </div>
                            <p class="mt-2 text-xs leading-relaxed text-slate-500">
                                @if ($r === \App\Enums\RolUsuario::ADMIN)
                                    Acceso total: gestión de empresas, usuarios, catálogos, ajustes y emisión de cartas.
                                @else
                                    Puede crear, redactar cartas, solicitar correlativo institucional y emitir documentos.
                                @endif
                            </p>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- CREDENCIALES INICIALES --}}
            <div class="border-t border-slate-100 pt-5">
                <h3 class="text-sm font-black text-slate-900 mb-3 flex items-center gap-2">
                    <x-icon name="lock" size="16" class="text-slate-400" /> Contraseña de Acceso
                </h3>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="password" class="form-label">Contraseña Inicial <span class="text-red-500">*</span></label>
                        <input id="password" name="password" type="password" class="form-input" minlength="8" placeholder="Mínimo 8 caracteres" required>
                    </div>

                    <div>
                        <label for="password_confirmation" class="form-label">Confirmar Contraseña <span class="text-red-500">*</span></label>
                        <input id="password_confirmation" name="password_confirmation" type="password" class="form-input" minlength="8" placeholder="Repite la contraseña" required>
                    </div>
                </div>
            </div>

            {{-- ESTADO ACTIVO --}}
            <div class="border-t border-slate-100 pt-4">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="activo" value="1" @checked(old('activo', '1') == '1') class="rounded border-slate-300 text-sdaya-600 shadow-sm focus:ring-sdaya-500 size-4">
                    <div>
                        <span class="font-bold text-slate-800 text-sm">Habilitar cuenta inmediatamente</span>
                        <p class="text-xs text-slate-400">Si está marcado, el usuario podrá ingresar al sistema de inmediato con estas credenciales.</p>
                    </div>
                </label>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-8">
            <a href="{{ route('usuarios.index') }}" class="btn-ghost text-xs">Cancelar</a>
            <button type="submit" class="btn-primary">
                <x-icon name="check" size="18" /> Registrar Usuario
            </button>
        </div>
    </form>
</x-layouts.app>
