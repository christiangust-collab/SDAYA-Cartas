<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

final class PerfilController extends Controller
{
    public function edit(Request $request): View
    {
        return view('perfil.edit', [
            'usuario' => $request->user()->load('empresaInstitucion'),
            'empresas' => \App\Models\Empresa::query()->activas()->orderBy('nombre')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $usuario = $request->user();

        $datos = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'cargo' => ['nullable', 'string', 'max:150'],
            'empresa_id' => ['nullable', 'integer', 'exists:empresas,id'],
            'empresa' => ['nullable', 'string', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'firma_digital' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'eliminar_firma' => ['nullable', 'boolean'],
        ]);

        $disk = Storage::disk(config('sdaya.documentos.disk', 'local'));
        $rutaFirma = $usuario->firma_digital;

        if ($request->boolean('eliminar_firma')) {
            if ($rutaFirma && $disk->exists($rutaFirma)) {
                $disk->delete($rutaFirma);
            }
            $rutaFirma = null;
        }

        if ($request->hasFile('firma_digital')) {
            if ($rutaFirma && $disk->exists($rutaFirma)) {
                $disk->delete($rutaFirma);
            }
            $archivo = $request->file('firma_digital');
            $extension = $archivo->getClientOriginalExtension() ?: 'png';
            $nombreArchivo = sprintf('firmas/firma_user_%d_%s.%s', $usuario->getKey(), time(), $extension);
            $disk->put($nombreArchivo, file_get_contents($archivo->getRealPath()));
            $rutaFirma = $nombreArchivo;
        }

        $actualizaciones = [
            'name' => trim((string) $datos['name']),
            'firma_digital' => $rutaFirma,
        ];

        if ($request->has('cargo')) {
            $actualizaciones['cargo'] = filled($datos['cargo'] ?? null) ? trim((string) $datos['cargo']) : null;
        }
        if (! empty($datos['empresa_id'])) {
            $actualizaciones['empresa_id'] = (int) $datos['empresa_id'];
            $empresaModel = \App\Models\Empresa::query()->find($datos['empresa_id']);
            if ($empresaModel) {
                $actualizaciones['empresa'] = $empresaModel->nombre;
            }
        } elseif ($request->has('empresa')) {
            $actualizaciones['empresa'] = filled($datos['empresa'] ?? null) ? trim((string) $datos['empresa']) : null;
        }
        if ($request->has('telefono')) {
            $actualizaciones['telefono'] = filled($datos['telefono'] ?? null) ? trim((string) $datos['telefono']) : null;
        }

        $usuario->update($actualizaciones);

        return redirect()->route('perfil.edit')
            ->with('success', 'Datos del perfil y pie de firma actualizados correctamente.');
    }
}
