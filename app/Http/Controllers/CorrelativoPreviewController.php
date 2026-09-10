<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\PreviewCorrelativoRequest;
use App\Http\Resources\CorrelativoPreviewResource;
use App\Models\Area;
use App\Models\Tipo;
use App\Services\CiteService;

final class CorrelativoPreviewController extends Controller
{
    public function __invoke(
        PreviewCorrelativoRequest $request,
        CiteService $cites,
    ): CorrelativoPreviewResource {
        $datos = $request->validated();
        $area = Area::query()->activas()->findOrFail($datos['area']);
        $tipo = Tipo::query()->activos()->findOrFail($datos['tipo']);

        $empresaId = $datos['empresa_id'] ?? null;
        $empresa = $empresaId ? \App\Models\Empresa::query()->find($empresaId) : null;
        if ($empresa === null) {
            $empresa = $request->user()?->empresaInstitucion ?? \App\Models\Empresa::actual();
        }

        return new CorrelativoPreviewResource([
            'cite' => $cites->vistaPrevia($area, $tipo, (int) $datos['anio'], $empresa),
        ]);
    }
}
