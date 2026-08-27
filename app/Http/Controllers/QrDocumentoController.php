<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Documento;
use Illuminate\Http\Response;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

final class QrDocumentoController extends Controller
{
    public function __invoke(Documento $documento): Response
    {
        $this->authorize('view', $documento);
        abort_unless($documento->estado->estaPublicado() && is_string($documento->hash_verificacion), 404);

        $svg = (string) QrCode::format('svg')
            ->size(280)
            ->margin(1)
            ->errorCorrection('M')
            ->generate(route('verificar.show', $documento->hash_verificacion));

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
