<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\DocxImportadorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Recibe un archivo DOCX del usuario, lo convierte a HTML compatible con
 * el editor Quill y lo devuelve como JSON.
 *
 * El HTML resultante ya fue sanitizado por HtmlSanitizer antes de enviarse.
 * La importación es una operación de solo lectura: no crea ningún documento,
 * no consume correlativos y no escribe en la base de datos.
 */
final class ImportarDocxController extends Controller
{
    /** Tamaño máximo aceptado en bytes (5 MB). */
    private const MAX_BYTES = 5 * 1024 * 1024;

    public function __invoke(Request $request, DocxImportadorService $importador): JsonResponse
    {
        $request->validate([
            'archivo' => [
                'required',
                'file',
                'mimes:docx',
                'max:'.intdiv(self::MAX_BYTES, 1024), // Laravel usa KB
            ],
        ], [
            'archivo.required' => 'Selecciona un archivo DOCX.',
            'archivo.mimes'    => 'El archivo debe ser un documento Word (.docx).',
            'archivo.max'      => 'El archivo no puede superar 5 MB.',
        ]);

        $archivo = $request->file('archivo');

        if ($archivo === null || ! $archivo->isValid()) {
            return response()->json(['error' => 'No se pudo procesar el archivo.'], 422);
        }

        try {
            $html = $importador->importar($archivo->getRealPath());
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Log::error('Error importando DOCX', ['mensaje' => $e->getMessage()]);

            return response()->json(['error' => 'Ocurrió un error inesperado al importar el documento.'], 500);
        }

        if (trim($html) === '') {
            return response()->json(['error' => 'El documento importado no contiene texto legible.'], 422);
        }

        return response()->json(['html' => $html]);
    }
}
