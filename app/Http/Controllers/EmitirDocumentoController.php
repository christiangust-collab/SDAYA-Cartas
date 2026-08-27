<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Documento;
use App\Services\EmitirDocumentoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

final class EmitirDocumentoController extends Controller
{
    public function __invoke(
        Request $request,
        Documento $documento,
        EmitirDocumentoService $emitir,
    ): RedirectResponse {
        $this->authorize('emitir', $documento);

        try {
            $emitido = $emitir->emitir($documento, $request->user());

            return redirect()->route('documentos.show', $emitido)
                ->with('success', 'Documento emitido y archivos generados correctamente.');
        } catch (ValidationException $exception) {
            return back()->with('error', $exception->validator->errors()->first());
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'No se pudo emitir el documento. No se realizaron cambios; inténtalo nuevamente o revisa el registro de la aplicación.');
        }
    }
}
