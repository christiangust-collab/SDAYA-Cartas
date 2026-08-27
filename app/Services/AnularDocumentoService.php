<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EstadoDocumento;
use App\Enums\EventoDocumento;
use App\Models\Documento;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class AnularDocumentoService
{
    public function __construct(private DocumentoAuditoriaService $auditoria) {}

    public function anular(Documento $documento, User $usuario, string $motivo): Documento
    {
        return DB::transaction(function () use ($documento, $usuario, $motivo): Documento {
            $bloqueado = Documento::query()->lockForUpdate()->findOrFail($documento->getKey());

            if (! $bloqueado->estaEmitido()) {
                throw ValidationException::withMessages([
                    'estado' => 'Solo se pueden anular documentos emitidos.',
                ]);
            }

            $bloqueado->update(['estado' => EstadoDocumento::ANULADO]);

            $this->auditoria->registrar(
                $bloqueado,
                $usuario,
                EventoDocumento::ANULADO,
                ['motivo' => trim($motivo)],
            );

            return $bloqueado->fresh(['area', 'tipo', 'emisor', 'eventos.usuario']);
        });
    }
}
