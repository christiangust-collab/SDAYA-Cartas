<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EventoDocumento;
use App\Models\Documento;
use App\Models\DocumentoEvento;
use App\Models\User;

final class DocumentoAuditoriaService
{
    /** @param array<string, mixed> $datos */
    public function registrar(
        Documento $documento,
        User $usuario,
        EventoDocumento $evento,
        array $datos = [],
    ): DocumentoEvento {
        return $documento->eventos()->create([
            'user_id' => $usuario->getKey(),
            'evento' => $evento,
            'datos' => $datos === [] ? null : $datos,
        ]);
    }
}
