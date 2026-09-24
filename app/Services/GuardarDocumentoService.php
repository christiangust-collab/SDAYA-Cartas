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
            $atributos = $this->atributos($datos, $usuario);
            $atributos['emitido_por'] = $usuario->getKey();
            $documento = Documento::query()->create($atributos);

            $this->auditoria->registrar(
                $documento,
                $usuario,
                EventoDocumento::CREADO,
                ['estado' => EstadoDocumento::BORRADOR->value],
            );

            return $documento->load(['area', 'tipo', 'firmante']);
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
            $atributos = $this->atributos($datos, $usuario);
            if (! $documento->emitido_por) {
                $atributos['emitido_por'] = $usuario->getKey();
            }
            $documento->fill($atributos);
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

            return $documento->load(['area', 'tipo', 'firmante']);
        });
    }

    /**
     * @param array<string, mixed> $datos
     * @return array<string, mixed>
     */
    private function atributos(array $datos, User $usuario): array
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

        $firmanteId = ! empty($datos['firmante_id']) ? (int) $datos['firmante_id'] : $usuario->getKey();
        $firmante = User::query()->find($firmanteId) ?? $usuario;
        $datosFirmante = $this->resolverDatosFirmante($datos['datos_firmante'] ?? null, $firmante);

        $empresaId = ! empty($datos['empresa_id'])
            ? (int) $datos['empresa_id']
            : ($firmante->empresa_id ?: $usuario->empresa_id);

        $empresa = $empresaId ? \App\Models\Empresa::query()->find($empresaId) : null;
        if ($empresa === null) {
            $empresa = \App\Models\Empresa::query()->where('activo', true)->first();
            $empresaId = $empresa?->id;
        }

        $datosEmpresa = $empresa ? [
            'id' => $empresa->id,
            'nombre' => $empresa->nombre,
            'nit' => (string) ($empresa->nit ?? ''),
            'direccion' => (string) ($empresa->direccion ?? ''),
            'telefono' => (string) ($empresa->telefono ?? ''),
            'correo' => (string) ($empresa->correo ?? ''),
            'sitio_web' => (string) ($empresa->sitio_web ?? ''),
            'logo' => $empresa->logo ? (string) $empresa->logo : null,
            'logo_documentos' => $empresa->logo_documentos ? (string) $empresa->logo_documentos : null,
        ] : null;

        $top = is_numeric($datos['margen_superior'] ?? null) ? (float) $datos['margen_superior'] : 3.0;
        $right = is_numeric($datos['margen_derecho'] ?? null) ? (float) $datos['margen_derecho'] : 2.5;
        $bottom = is_numeric($datos['margen_inferior'] ?? null) ? (float) $datos['margen_inferior'] : 3.0;
        $left = is_numeric($datos['margen_izquierdo'] ?? null) ? (float) $datos['margen_izquierdo'] : 3.0;

        $top = max(0.0, min(10.0, round($top, 1)));
        $right = max(0.0, min(10.0, round($right, 1)));
        $bottom = max(0.0, min(10.0, round($bottom, 1)));
        $left = max(0.0, min(10.0, round($left, 1)));

        $cadenaMargenes = "{$top},{$right},{$bottom},{$left}";

        $payload = [
            'area_id' => $area->getKey(),
            'tipo_id' => $tipo->getKey(),
            'empresa_id' => $empresaId,
            'datos_empresa' => $datosEmpresa,
            'anio' => (int) $fecha->format('Y'),
            'fecha_documento' => $fecha,
            'lugar' => $this->lugar($datos),
            'alineacion_encabezado' => in_array($datos['alineacion_encabezado'] ?? '', ['left', 'center', 'right'], true)
                ? $datos['alineacion_encabezado']
                : 'right',
            'alineacion_pie_firma' => in_array($datos['alineacion_pie_firma'] ?? '', ['left', 'center', 'right'], true)
                ? $datos['alineacion_pie_firma']
                : (in_array($datos['alineacion_encabezado'] ?? '', ['left', 'center', 'right'], true) ? $datos['alineacion_encabezado'] : 'right'),
            'asunto' => $datos['asunto'] ?? null,
            'destinatario' => $datos['destinatario'] ?? null,
            'firmante_id' => $firmante->getKey(),
            'datos_firmante' => $datosFirmante,
            'contenido' => $contenido,
            'estado' => EstadoDocumento::BORRADOR,
        ];

        if (\Illuminate\Support\Facades\Schema::hasColumn('documentos', 'margenes')) {
            $payload['margenes'] = $cadenaMargenes;
        } else {
            $limpio = preg_replace('/<span\s+data-sdaya-margenes="[^"]*"\s*(?:style="[^"]*")?\s*><\/span>/si', '', (string) $contenido);
            $payload['contenido'] = '<span data-sdaya-margenes="'.$cadenaMargenes.'" style="display:none"></span>'.$limpio;
        }

        return $payload;
    }

    /**
     * @param array<string, mixed>|null $custom
     * @return array{nombre: string, cargo: string, empresa: string, correo: string, telefono: string, firma_digital: ?string}
     */
    private function resolverDatosFirmante(?array $custom, User $firmante): array
    {
        $firma = ! empty($custom['firma_digital'])
            ? (string) $custom['firma_digital']
            : ($firmante->firma_digital ? (string) $firmante->firma_digital : null);

        $empresaNombre = trim((string) (
            $custom['empresa']
            ?? $firmante->empresaInstitucion?->nombre
            ?? (is_string($firmante->empresa ?? null) && filled($firmante->empresa) ? $firmante->empresa : null)
            ?? 'SDAYA S.R.L.'
        ));

        return [
            'nombre' => trim((string) ($custom['nombre'] ?? $firmante->name)),
            'cargo' => trim((string) ($custom['cargo'] ?? $firmante->cargo ?? '')),
            'empresa' => $empresaNombre,
            'correo' => trim((string) ($custom['correo'] ?? $firmante->email)),
            'telefono' => trim((string) ($custom['telefono'] ?? $firmante->telefono ?? '')),
            'firma_digital' => $firma,
        ];
    }

    /** @param array<string, mixed> $datos */
    private function lugar(array $datos): string
    {
        $lugar = trim((string) ($datos['lugar'] ?? ''));

        return $lugar !== '' ? $lugar : (string) config('sdaya.marca.lugar', 'La Paz');
    }
}
