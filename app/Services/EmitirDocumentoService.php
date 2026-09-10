<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\GeneradorArchivosDocumento;
use App\Enums\EstadoDocumento;
use App\Enums\EventoDocumento;
use App\Models\Documento;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class EmitirDocumentoService
{
    public function __construct(
        private CiteService $cites,
        private DocumentoHashService $hashes,
        private DocumentoAuditoriaService $auditoria,
        private GeneradorArchivosDocumento $archivos,
    ) {}

    public function emitir(Documento $documento, User $usuario): Documento
    {
        return DB::transaction(function () use ($documento, $usuario): Documento {
            $bloqueado = Documento::query()
                ->with(['area', 'tipo'])
                ->lockForUpdate()
                ->findOrFail($documento->getKey());

            if (! $bloqueado->estaBorrador()) {
                throw ValidationException::withMessages([
                    'estado' => 'El documento ya no está disponible para emisión.',
                ]);
            }

            if (! $bloqueado->area->activo || ! $bloqueado->tipo->activo) {
                throw ValidationException::withMessages([
                    'estado' => 'El área y el tipo deben estar activos antes de emitir.',
                ]);
            }

            $empresa = $bloqueado->empresa
                ?? ($bloqueado->empresa_id ? \App\Models\Empresa::query()->find($bloqueado->empresa_id) : null)
                ?? $bloqueado->firmante?->empresaInstitucion
                ?? \App\Models\Empresa::actual();

            $asignado = $this->cites->reservarSiguiente(
                $bloqueado->area,
                $bloqueado->tipo,
                $bloqueado->anio,
                $empresa,
            );

            $firmante = $bloqueado->firmante ?? $usuario;
            $datosEmpresa = $bloqueado->datosEmpresa();
            $datosFirmante = $bloqueado->pieFirma() ?? $firmante->datosPieFirma();

            $bloqueado->forceFill([
                'cite' => $asignado->cite,
                'correlativo' => $asignado->correlativo,
                'estado' => EstadoDocumento::EMITIDO,
                'hash_verificacion' => bin2hex(random_bytes(32)),
                'emitido_por' => $usuario->getKey(),
                'emitido_at' => now(),
                'empresa_id' => $bloqueado->empresa_id ?: ($datosEmpresa['id'] ?? null),
                'datos_empresa' => $datosEmpresa,
                'firmante_id' => $bloqueado->firmante_id ?: $firmante->getKey(),
                'datos_firmante' => $datosFirmante,
            ]);
            $bloqueado->hash_contenido = $this->hashes->contenido($bloqueado);
            $bloqueado->save();

            $generados = $this->archivos->generar($bloqueado);

            $bloqueado->forceFill([
                'archivo_docx' => $generados->docx,
                'archivo_pdf' => $generados->pdf,
                'hash_archivo_pdf' => $generados->hashPdf,
            ])->save();

            $this->auditoria->registrar(
                $bloqueado,
                $usuario,
                EventoDocumento::EMITIDO,
                ['cite' => $bloqueado->cite],
            );

            return $bloqueado->fresh(['area', 'tipo', 'emisor', 'firmante', 'eventos.usuario']);
        }, 3);
    }
}
