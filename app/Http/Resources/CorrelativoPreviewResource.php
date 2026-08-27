<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CorrelativoPreviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'cite' => $this->resource['cite'],
            'es_reserva' => false,
            'mensaje' => 'Vista previa informativa; el número se confirma al emitir.',
        ];
    }
}
