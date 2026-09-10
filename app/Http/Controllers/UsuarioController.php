<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\RolUsuario;
use App\Http\Requests\ActualizarUsuarioRequest;
use App\Http\Requests\GuardarUsuarioRequest;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class UsuarioController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        $usuarios = User::query()
            ->with('empresaInstitucion')
            ->withCount('documentosEmitidos')
            ->orderByDesc('activo')
            ->orderBy('name')
            ->get();

        return view('usuarios.index', [
            'usuarios' => $usuarios,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        $empresas = Empresa::query()->activas()->orderBy('nombre')->get();
        if ($empresas->isEmpty()) {
            $empresas = Empresa::query()->orderBy('nombre')->get();
        }

        return view('usuarios.create', [
            'empresas' => $empresas,
            'roles' => RolUsuario::cases(),
        ]);
    }

    public function store(GuardarUsuarioRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $datos['activo'] = $request->boolean('activo', true);

        if (! empty($datos['empresa_id'])) {
            $empresa = Empresa::query()->find($datos['empresa_id']);
            if ($empresa) {
                $datos['empresa'] = $empresa->nombre;
            }
        }

        User::query()->create($datos);

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario registrado correctamente.');
    }

    public function edit(User $usuario): View
    {
        $this->authorize('update', $usuario);

        $empresas = Empresa::query()->activas()->orderBy('nombre')->get();
        if ($empresas->isEmpty()) {
            $empresas = Empresa::query()->orderBy('nombre')->get();
        }

        return view('usuarios.edit', [
            'usuario' => $usuario->load('empresaInstitucion'),
            'empresas' => $empresas,
            'roles' => RolUsuario::cases(),
        ]);
    }

    public function update(ActualizarUsuarioRequest $request, User $usuario): RedirectResponse
    {
        $datos = $request->validated();

        if (blank($datos['password'] ?? null)) {
            unset($datos['password']);
        }

        $nuevoEstado = $request->boolean('activo', $usuario->activo);
        if ($usuario->id === $request->user()?->id && ! $nuevoEstado) {
            return back()->withErrors(['activo' => 'No puedes desactivar tu propia cuenta de administrador.']);
        }
        $datos['activo'] = $nuevoEstado;

        if (! empty($datos['empresa_id'])) {
            $empresa = Empresa::query()->find($datos['empresa_id']);
            if ($empresa) {
                $datos['empresa'] = $empresa->nombre;
            }
        }

        $usuario->update($datos);

        return redirect()->route('usuarios.index')
            ->with('success', "Información del usuario \"{$usuario->name}\" actualizada correctamente.");
    }

    public function toggle(User $usuario): RedirectResponse
    {
        $this->authorize('toggle', $usuario);

        $usuario->update(['activo' => ! $usuario->activo]);

        $estado = $usuario->activo ? 'activado' : 'desactivado';

        return back()->with('success', "El usuario \"{$usuario->name}\" fue {$estado} correctamente.");
    }
}
