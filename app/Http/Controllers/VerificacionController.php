<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Documento;
use App\Services\DocumentoHashService;
use Illuminate\View\View;

final class VerificacionController extends Controller
{
    public function __invoke(string $hash, DocumentoHashService $hashes): View
    {
        $documento = Documento::query()
            ->conRelaciones()
            ->where('hash_verificacion', $hash)
            ->first();

        return view('verificacion.show', [
            'documento' => $documento,
            'contenidoIntegro' => $documento?->estaEmitido() ? $hashes->contenidoEsIntegro($documento) : false,
            'pdfIntegro' => $documento?->estaEmitido() ? $hashes->pdfEsIntegro($documento) : false,
        ]);
    }
}
