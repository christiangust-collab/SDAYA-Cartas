<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\RolUsuario;
use App\Http\Requests\GuardarEmpresaRequest;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

final class EmpresaController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Empresa::class);

        return view('empresas.index', [
            'empresas' => Empresa::query()
                ->withCount(['documentos', 'users'])
                ->orderByDesc('activo')
                ->orderBy('nombre')
                ->get(),
            'firmantes' => User::query()
                ->with('empresaInstitucion')
                ->whereIn('role', [RolUsuario::ADMIN, RolUsuario::EDITOR])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Empresa::class);

        return view('empresas.create');
    }

    public function store(GuardarEmpresaRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $disco = Storage::disk(config('sdaya.documentos.disk', 'local'));

        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            $archivo = $request->file('logo');
            $extension = $archivo->getClientOriginalExtension() ?: 'png';
            $nombreArchivo = 'logos/empresa_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
            $disco->put($nombreArchivo, file_get_contents($archivo->getRealPath()));
            $datos['logo'] = $nombreArchivo;
        }

        $datos['activo'] = $request->boolean('activo', true);

        Empresa::query()->create($datos);

        return redirect()->route('empresas.index')
            ->with('success', 'Empresa registrada correctamente.');
    }

    public function edit(Empresa $empresa): View
    {
        $this->authorize('update', $empresa);

        return view('empresas.edit', [
            'empresa' => $empresa,
        ]);
    }

    public function update(GuardarEmpresaRequest $request, Empresa $empresa): RedirectResponse
    {
        $datos = $request->validated();
        $disco = Storage::disk(config('sdaya.documentos.disk', 'local'));

        if ($request->boolean('eliminar_logo') && filled($empresa->logo)) {
            if ($disco->exists((string) $empresa->logo)) {
                $disco->delete((string) $empresa->logo);
            }
            $datos['logo'] = null;
        }

        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            if (filled($empresa->logo) && $disco->exists((string) $empresa->logo)) {
                $disco->delete((string) $empresa->logo);
            }
            $archivo = $request->file('logo');
            $extension = $archivo->getClientOriginalExtension() ?: 'png';
            $nombreArchivo = 'logos/empresa_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
            $disco->put($nombreArchivo, file_get_contents($archivo->getRealPath()));
            $datos['logo'] = $nombreArchivo;
        }

        $datos['activo'] = $request->boolean('activo', $empresa->activo);

        $empresa->update($datos);

        return redirect()->route('empresas.index')
            ->with('success', 'Información de la empresa actualizada correctamente.');
    }

    public function toggle(Empresa $empresa): RedirectResponse
    {
        $this->authorize('toggle', $empresa);

        $empresa->update(['activo' => ! $empresa->activo]);

        $estado = $empresa->activo ? 'activada' : 'desactivada';

        return back()->with('success', "La empresa \"{$empresa->nombre}\" fue {$estado} correctamente.");
    }

    public function asignarFirmante(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Empresa::class);

        $validados = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'empresa_id' => ['required', 'integer', 'exists:empresas,id'],
            'cargo' => ['nullable', 'string', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:50'],
        ]);

        $usuario = User::query()->findOrFail($validados['user_id']);
        $usuario->update([
            'empresa_id' => $validados['empresa_id'],
            'cargo' => $validados['cargo'] ?? $usuario->cargo,
            'telefono' => $validados['telefono'] ?? $usuario->telefono,
        ]);

        return back()->with('success', "Firmante \"{$usuario->name}\" asignado a la empresa correctamente.");
    }
}