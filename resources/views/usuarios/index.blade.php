<x-layouts.app>
    <x-slot:title>Gestión de Usuarios</x-slot:title>
    <x-slot:heading>Usuarios y Cuentas</x-slot:heading>
    <x-slot:subheading>Administra los accesos institucionales al sistema, asignación de roles y vinculación con empresas.</x-slot:subheading>

    @if (session('success'))
        <div class="mb-6 flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-900 shadow-sm" role="status">
            <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-emerald-100 text-emerald-700">
                <x-icon name="check" size="18" />
            </span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-900" role="alert">
            <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-red-100 text-red-700">
                <x-icon name="alert-circle" size="18" />
            </span>
            <div>
                <p class="font-extrabold">Se encontraron errores:</p>
                <ul class="mt-2 list-disc pl-5 font-medium">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-2 text-xs font-bold text-slate-500">
            <span class="inline-flex size-2 rounded-full bg-emerald-500"></span>
            Total de usuarios registrados: <strong class="text-slate-900">{{ $usuarios->count() }}</strong>
        </div>

        <a href="{{ route('usuarios.create') }}" class="btn-primary shrink-0 inline-flex items-center gap-2">
            <x-icon name="user-plus" size="18" /> Nuevo Usuario
        </a>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="border-b border-slate-200 bg-slate-50/75 text-[11px] font-black uppercase tracking-wider text-slate-500">
                    <tr>
                        <th scope="col" class="px-5 py-3.5">Usuario</th>
                        <th scope="col" class="px-5 py-3.5">Rol de Sistema</th>
                        <th scope="col" class="px-5 py-3.5">Empresa / Cargo</th>
                        <th scope="col" class="px-5 py-3.5 text-center">Documentos</th>
                        <th scope="col" class="px-5 py-3.5 text-center">Estado</th>
                        <th scope="col" class="px-5 py-3.5 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse ($usuarios as $usr)
                        <tr class="transition-colors hover:bg-slate-50/60 {{ ! $usr->estaActivo() ? 'opacity-60 bg-slate-50/40' : '' }}">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="grid size-10 shrink-0 place-items-center rounded-xl font-black text-sm shadow-sm {{ $usr->role === \App\Enums\RolUsuario::ADMIN ? 'bg-indigo-100 text-indigo-800' : ($usr->role === \App\Enums\RolUsuario::EDITOR ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700') }}">
                                        {{ mb_substr($usr->name, 0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate font-extrabold text-slate-900 flex items-center gap-1.5">
                                            {{ $usr->name }}
                                            @if ($usr->id === auth()->id())
                                                <span class="rounded bg-sdaya-100 px-1.5 py-0.5 text-[10px] font-bold text-sdaya-800">Tú</span>
                                            @endif
                                        </p>
                                        <p class="truncate text-xs font-mono text-slate-400">{{ $usr->email }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-5 py-4 whitespace-nowrap">
                                @if ($usr->role === \App\Enums\RolUsuario::ADMIN)
                                    <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-700 ring-1 ring-indigo-200">
                                        {{ $usr->role->etiqueta() }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 ring-1 ring-emerald-200">
                                        {{ $usr->role->etiqueta() }}
                                    </span>
                                @endif
                            </td>

                            <td class="px-5 py-4">
                                <p class="font-bold text-slate-800 truncate">
                                    {{ $usr->empresaInstitucion?->nombre ?? ($usr->empresa ?: 'Sin empresa asignada') }}
                                </p>
                                @if ($usr->cargo)
                                    <p class="text-xs text-slate-400 truncate">{{ $usr->cargo }}</p>
                                @endif
                            </td>

                            <td class="px-5 py-4 text-center whitespace-nowrap">
                                <span class="rounded-lg bg-slate-100 px-2 py-1 text-xs font-bold text-slate-700">
                                    {{ $usr->documentos_emitidos_count }}
                                </span>
                            </td>

                            <td class="px-5 py-4 text-center whitespace-nowrap">
                                @if ($usr->estaActivo())
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-bold text-emerald-700 ring-1 ring-emerald-200">
                                        <span class="size-1.5 rounded-full bg-emerald-500"></span> Activo
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-bold text-rose-700 ring-1 ring-rose-200">
                                        <span class="size-1.5 rounded-full bg-rose-500"></span> Inactivo
                                    </span>
                                @endif
                            </td>

                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('usuarios.edit', $usr) }}" class="rounded-xl border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-sdaya-900 inline-flex items-center gap-1">
                                        <x-icon name="edit" size="14" class="text-slate-400" /> Editar
                                    </a>

                                    @if ($usr->id !== auth()->id())
                                        <form method="POST" action="{{ route('usuarios.toggle', $usr) }}" class="inline-block" onsubmit="return confirm('¿Estás seguro de {{ $usr->estaActivo() ? 'desactivar' : 'activar' }} al usuario {{ $usr->name }}?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="rounded-xl border px-2.5 py-1.5 text-xs font-bold transition shadow-sm {{ $usr->estaActivo() ? 'border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100' : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                                                {{ $usr->estaActivo() ? 'Desactivar' : 'Activar' }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-slate-400">
                                <x-icon name="users" size="36" class="mx-auto text-slate-300 mb-2" />
                                <p class="font-bold text-slate-600">No se encontraron usuarios registrados.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
