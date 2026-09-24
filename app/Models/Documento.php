<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EstadoDocumento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Documento extends Model
{
    use HasFactory;

    protected $fillable = [
        'cite',
        'area_id',
        'tipo_id',
        'empresa_id',
        'datos_empresa',
        'anio',
        'correlativo',
        'fecha_documento',
        'lugar',
        'alineacion_encabezado',
        'alineacion_pie_firma',
        'margenes',
        'asunto',
        'destinatario',
        'firmante_id',
        'datos_firmante',
        'contenido',
        'estado',
        'hash_verificacion',
        'hash_contenido',
        'hash_archivo_pdf',
        'archivo_docx',
        'archivo_pdf',
        'emitido_por',
        'emitido_at',
    ];

    protected function casts(): array
    {
        return [
            'fecha_documento' => 'date',
            'emitido_at' => 'datetime',
            'estado' => EstadoDocumento::class,
            'datos_firmante' => 'array',
            'datos_empresa' => 'array',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(Tipo::class);
    }

    public function firmante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'firmante_id');
    }

    public function emisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'emitido_por');
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(DocumentoEvento::class)->latest();
    }

    /** @return array{id: ?int, nombre: string, nit: string, direccion: string, telefono: string, correo: string, sitio_web: string, logo: ?string, logo_documentos: ?string} */
    public function datosEmpresa(): array
    {
        if (is_array($this->datos_empresa) && filled($this->datos_empresa['nombre'] ?? null)) {
            return [
                'id' => isset($this->datos_empresa['id']) ? (int) $this->datos_empresa['id'] : $this->empresa_id,
                'nombre' => (string) ($this->datos_empresa['nombre'] ?? config('sdaya.marca.nombre', 'SDAYA S.R.L.')),
                'nit' => (string) ($this->datos_empresa['nit'] ?? ''),
                'direccion' => (string) ($this->datos_empresa['direccion'] ?? ''),
                'telefono' => (string) ($this->datos_empresa['telefono'] ?? ''),
                'correo' => (string) ($this->datos_empresa['correo'] ?? ''),
                'sitio_web' => (string) ($this->datos_empresa['sitio_web'] ?? ''),
                'logo' => ! empty($this->datos_empresa['logo']) ? (string) $this->datos_empresa['logo'] : null,
                'logo_documentos' => ! empty($this->datos_empresa['logo_documentos'])
                    ? (string) $this->datos_empresa['logo_documentos']
                    : ($this->empresa?->logo_documentos ?: null),
            ];
        }

        $empresa = $this->empresa;
        if (! ($empresa instanceof Empresa)) {
            $empresa = $this->firmante?->empresaInstitucion ?? $this->emisor?->empresaInstitucion;
        }

        if ($empresa instanceof Empresa) {
            return [
                'id' => $empresa->id,
                'nombre' => (string) $empresa->nombre,
                'nit' => (string) ($empresa->nit ?? ''),
                'direccion' => (string) ($empresa->direccion ?? ''),
                'telefono' => (string) ($empresa->telefono ?? ''),
                'correo' => (string) ($empresa->correo ?? ''),
                'sitio_web' => (string) ($empresa->sitio_web ?? ''),
                'logo' => $empresa->logo ? (string) $empresa->logo : null,
                'logo_documentos' => $empresa->logo_documentos ? (string) $empresa->logo_documentos : null,
            ];
        }

        $nombreEmpresaFallback = is_string($this->firmante?->empresa ?? null) && filled($this->firmante->empresa)
            ? $this->firmante->empresa
            : (is_string($this->emisor?->empresa ?? null) && filled($this->emisor->empresa) ? $this->emisor->empresa : 'SDAYA S.R.L.');

        return [
            'id' => null,
            'nombre' => (string) $nombreEmpresaFallback,
            'nit' => '1028374029',
            'direccion' => 'Av. 20 de Octubre, Edif. Los Pinos, Piso 4, La Paz - Bolivia',
            'telefono' => '+591 2 2123456',
            'correo' => 'contacto@sdaya.com.bo',
            'sitio_web' => 'https://sdaya.com.bo',
            'logo' => null,
            'logo_documentos' => null,
        ];
    }

    /** @return array{nombre: string, cargo: string, empresa: string, correo: string, telefono: string, firma_digital: ?string}|null */
    public function pieFirma(): ?array
    {
        $empresaNombre = $this->datosEmpresa()['nombre'];

        if (is_array($this->datos_firmante) && filled($this->datos_firmante['nombre'] ?? null)) {
            return [
                'nombre' => (string) ($this->datos_firmante['nombre'] ?? ''),
                'cargo' => (string) ($this->datos_firmante['cargo'] ?? ''),
                'empresa' => (string) ($this->datos_firmante['empresa'] ?? $empresaNombre),
                'correo' => (string) ($this->datos_firmante['correo'] ?? ''),
                'telefono' => (string) ($this->datos_firmante['telefono'] ?? ''),
                'firma_digital' => ! empty($this->datos_firmante['firma_digital']) ? (string) $this->datos_firmante['firma_digital'] : null,
            ];
        }

        if ($this->firmante !== null) {
            return $this->firmante->datosPieFirma();
        }

        if ($this->emisor !== null) {
            return $this->emisor->datosPieFirma();
        }

        return null;
    }

    public function firmaDataUri(): ?string
    {
        $pie = $this->pieFirma();
        $ruta = $pie['firma_digital'] ?? null;

        if (! filled($ruta)) {
            return null;
        }

        $disco = \Illuminate\Support\Facades\Storage::disk(config('sdaya.documentos.disk', 'local'));
        if (! $disco->exists((string) $ruta)) {
            return null;
        }

        $contenido = $disco->get((string) $ruta);
        $mime = $disco->mimeType((string) $ruta) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($contenido);
    }

    public function membreteDataUri(): ?string
    {
        $datos = $this->datosEmpresa();
        $ruta = $datos['logo_documentos'] ?? null;

        if (filled($ruta)) {
            $disco = \Illuminate\Support\Facades\Storage::disk(config('sdaya.documentos.disk', 'local'));
            if ($disco->exists((string) $ruta)) {
                $contenido = $disco->get((string) $ruta);
                $mime = $disco->mimeType((string) $ruta) ?: 'image/png';

                return 'data:'.$mime.';base64,'.base64_encode($contenido);
            }
        }

        return null;
    }

    public function scopeConRelaciones(Builder $query): Builder
    {
        return $query->with(['area', 'tipo', 'emisor', 'firmante', 'empresa']);
    }

    public function scopeParaUsuario(Builder $query, User $usuario): Builder
    {
        if ($usuario->esAdministrador()) {
            return $query;
        }

        if ($usuario->empresa_id !== null) {
            return $query->where('empresa_id', $usuario->empresa_id);
        }

        return $query;
    }

    public function scopeFiltrar(Builder $query, array $filtros): Builder
    {
        return $query
            ->when($filtros['buscar'] ?? null, function (Builder $query, string $buscar): void {
                $query->where(function (Builder $query) use ($buscar): void {
                    $patron = '%'.mb_strtolower(trim($buscar)).'%';

                    $query
                        ->whereRaw('LOWER(COALESCE(cite, ?)) LIKE ?', ['', $patron])
                        ->orWhereRaw('LOWER(COALESCE(asunto, ?)) LIKE ?', ['', $patron])
                        ->orWhereRaw('LOWER(COALESCE(destinatario, ?)) LIKE ?', ['', $patron])
                        ->orWhereRaw('LOWER(contenido) LIKE ?', [$patron]);
                });
            })
            ->when($filtros['empresa_id'] ?? null, fn (Builder $query, mixed $id): Builder => $query->where('empresa_id', $id))
            ->when($filtros['area_id'] ?? null, fn (Builder $query, mixed $id): Builder => $query->where('area_id', $id))
            ->when($filtros['tipo_id'] ?? null, fn (Builder $query, mixed $id): Builder => $query->where('tipo_id', $id))
            ->when($filtros['anio'] ?? null, fn (Builder $query, mixed $anio): Builder => $query->where('anio', $anio))
            ->when($filtros['estado'] ?? null, fn (Builder $query, mixed $estado): Builder => $query->where('estado', $estado));
    }

    public function estaBorrador(): bool
    {
        return $this->estado === EstadoDocumento::BORRADOR;
    }

    public function estaEmitido(): bool
    {
        return $this->estado === EstadoDocumento::EMITIDO;
    }

    public function estaAnulado(): bool
    {
        return $this->estado === EstadoDocumento::ANULADO;
    }

    /**
     * Devuelve los márgenes de página en centímetros [top, right, bottom, left].
     * Por defecto retorna los valores institucionales (3.0 cm, 2.5 cm, 3.0 cm, 3.0 cm).
     *
     * @return array{top: float, right: float, bottom: float, left: float}
     */
    public function margenes(): array
    {
        if (isset($this->attributes['margenes']) && filled($this->attributes['margenes'])) {
            $partes = explode(',', (string) $this->attributes['margenes']);
            if (count($partes) === 4) {
                return [
                    'top' => (float) $partes[0],
                    'right' => (float) $partes[1],
                    'bottom' => (float) $partes[2],
                    'left' => (float) $partes[3],
                ];
            }
        }

        if (preg_match('/data-sdaya-margenes="([0-9.,]+)"/', (string) ($this->contenido ?? ''), $matches)) {
            $partes = explode(',', $matches[1]);
            if (count($partes) === 4) {
                return [
                    'top' => (float) $partes[0],
                    'right' => (float) $partes[1],
                    'bottom' => (float) $partes[2],
                    'left' => (float) $partes[3],
                ];
            }
        }

        return [
            'top' => 3.0,
            'right' => 2.5,
            'bottom' => 3.0,
            'left' => 3.0,
        ];
    }
}
