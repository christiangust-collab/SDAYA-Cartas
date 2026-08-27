<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\GuardarAreaRequest;
use App\Models\Area;
use Illuminate\Http\RedirectResponse;

final class AreaController extends Controller
{
    public function store(GuardarAreaRequest $request): RedirectResponse
    {
        Area::query()->create($request->validated());

        return back()->with('success', 'Área creada correctamente.')->withFragment('areas');
    }

    public function update(GuardarAreaRequest $request, Area $area): RedirectResponse
    {
        $area->update($request->validated());

        return back()->with('success', 'Área actualizada correctamente.')->withFragment('areas');
    }

    public function toggle(Area $area): RedirectResponse
    {
        $this->authorize('toggle', $area);
        $area->update(['activo' => ! $area->activo]);

        $mensaje = $area->activo ? 'Área activada.' : 'Área desactivada.';

        return back()->with('success', $mensaje)->withFragment('areas');
    }
}
