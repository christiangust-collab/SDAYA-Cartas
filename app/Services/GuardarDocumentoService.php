<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EstadoDocumento;
use App\Enums\EventoDocumento;
use App\Models\Area;
use App\Models\Documento;
use App\Models\Tipo;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class GuardarDocumentoService
{
    public function __construct(
        private HtmlSanitizer $sanitizer,
        private DocumentoAuditoriaService $auditoria,
    ) {}

    /** @param array<string, mixed> $datos */
    public function crear(array $datos, User $usuario): Documento
    {
        return DB::transaction(function () use ($datos, $usuario): Documento {
            $documento = Documento::query()->create($this->atributos($datos));

            $this->auditoria->registrar(
                $documento,
                $usuario,
                EventoDocumento::CREADO,
                ['estado' => EstadoDocumento::BORRADOR->value],
            );

            return $documento->load(['area', 'tipo']);
        });
    }

    /** @param array<string, mixed> $datos */
    public function actualizar(Documento $documento, array $datos, User $usuario): Documento
    {
        if (! $documento->estaBorrador()) {
            throw ValidationException::withMessages([
                'estado' => 'Solo se pueden editar documentos en borrador.',
            ]);
        }

        return DB::transaction(function () use ($documento, $datos, $usuario): Documento {
            $documento->fill($this->atributos($datos));
            $cambios = array_keys($documento->getDirty());
            $documento->save();

            $this->auditoria->registrar(
                $documento,
                $usuario,
                EventoDocumento::ACTUALIZADO,
                [
                    'campos' => array_values(array_diff($cambios, ['contenido'])),
                    'contenido_modificado' => in_array('contenido', $cambios, true),
                ],
            );

            return $documento->load(['area', 'tipo']);
        });
    }

    /** @param array<string, mixed> $datos
     * @return array<string, mixed>
     */
    private function atributos(array $datos): array
    {
        $fecha = CarbonImmutable::createFromFormat('Y-m-d', (string) $datos['fecha_documento']);
        $area = Area::query()->activas()->find($datos['area_id']);
        $tipo = Tipo::query()->activos()->find($datos['tipo_id']);

        if ($area === null || $tipo === null) {
            $errores = [];

            if ($area === null) {
                $errores['area_id'] = 'Selecciona un área activa.';
            }

            if ($tipo === null) {
                $errores['tipo_id'] = 'Selecciona un tipo activo.';
            }

            throw ValidationException::withMessages($errores);
        }

        $contenido = $this->sanitizer->limpiar((string) $datos['contenido']);

        if (trim(html_entity_decode(strip_tags($contenido), ENT_QUOTES | ENT_HTML5, 'UTF-8')) === '') {
            throw ValidationException::withMessages([
                'contenido' => 'El contenido quedó vacío después de aplicar la limpieza de seguridad.',
            ]);
        }

        return [
            'area_id' => $area->getKey(),
            'tipo_id' => $tipo->getKey(),
            'anio' => (int) $fecha->format('Y'),
            'fecha_documento' => $fecha,
            'lugar' => $this->lugar($datos),
            'alineacion_encabezado' => in_array($datos['alineacion_encabezado'] ?? '', ['left', 'center', 'right'], true)
                ? $datos['alineacion_encabezado']
                : 'right',
            'asunto' => $datos['asunto'] ?? null,
            'destinatario' => $datos['destinatario'] ?? null,
            'contenido' => $contenido,
            'estado' => EstadoDocumento::BORRADOR,
        ];
    }

    /** @param array<string, mixed> $datos */
    private function lugar(array $datos): string
    {
        $lugar = trim((string) ($datos['lugar'] ?? ''));

        return $lugar !== '' ? $lugar : (string) config('sdaya.marca.lugar', 'La Paz');
    }
}
