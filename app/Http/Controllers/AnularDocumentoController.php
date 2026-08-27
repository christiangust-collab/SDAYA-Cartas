<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AnularDocumentoRequest;
use App\Models\Documento;
use App\Services\AnularDocumentoService;
use Illuminate\Http\RedirectResponse;

final class AnularDocumentoController extends Controller
{
    public function __invoke(
        AnularDocumentoRequest $request,
        Documento $documento,
        AnularDocumentoService $anular,
    ): RedirectResponse {
        $anulado = $anular->anular(
            $documento,
            $request->user(),
            (string) $request->validated('motivo'),
        );

        return redirect()->route('documentos.show', $anulado)
            ->with('success', 'Documento anulado. El historial conserva la emisión original.');
    }
}
