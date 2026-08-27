<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Documento;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use JsonException;

final class DocumentoHashService
{
    /** @throws JsonException */
    public function contenido(Documento $documento): string
    {
        $canonico = json_encode([
            'cite' => $documento->cite,
            'area_id' => $documento->area_id,
            'tipo_id' => $documento->tipo_id,
            'anio' => $documento->anio,
            'correlativo' => $documento->correlativo,
            'fecha_documento' => $documento->fecha_documento?->format('Y-m-d'),
            'asunto' => $documento->asunto,
            'destinatario' => $documento->destinatario,
            'contenido' => $documento->contenido,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return hash('sha256', $canonico);
    }

    public function contenidoEsIntegro(Documento $documento): bool
    {
        return is_string($documento->hash_contenido)
            && hash_equals($documento->hash_contenido, $this->contenido($documento));
    }

    public function pdfEsIntegro(Documento $documento): bool
    {
        if (! is_string($documento->archivo_pdf) || ! is_string($documento->hash_archivo_pdf)) {
            return false;
        }

        $disco = $this->disco();

        if (! $disco->exists($documento->archivo_pdf)) {
            return false;
        }

        return hash_equals(
            $documento->hash_archivo_pdf,
            hash('sha256', (string) $disco->get($documento->archivo_pdf)),
        );
    }

    private function disco(): Filesystem
    {
        return Storage::disk((string) config('sdaya.documentos.disk', 'local'));
    }
}
