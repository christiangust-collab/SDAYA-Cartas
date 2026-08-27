<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\GuardarTipoRequest;
use App\Models\Tipo;
use Illuminate\Http\RedirectResponse;

final class TipoController extends Controller
{
    public function store(GuardarTipoRequest $request): RedirectResponse
    {
        Tipo::query()->create($request->validated());

        return back()->with('success', 'Tipo creado correctamente.')->withFragment('tipos');
    }

    public function update(GuardarTipoRequest $request, Tipo $tipo): RedirectResponse
    {
        $tipo->update($request->validated());

        return back()->with('success', 'Tipo actualizado correctamente.')->withFragment('tipos');
    }

    public function toggle(Tipo $tipo): RedirectResponse
    {
        $this->authorize('toggle', $tipo);
        $tipo->update(['activo' => ! $tipo->activo]);

        $mensaje = $tipo->activo ? 'Tipo activado.' : 'Tipo desactivado.';

        return back()->with('success', $mensaje)->withFragment('tipos');
    }
}
