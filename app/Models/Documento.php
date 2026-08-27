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
        'anio',
        'correlativo',
        'fecha_documento',
        'lugar',
        'alineacion_encabezado',
        'asunto',
        'destinatario',
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
        ];
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(Tipo::class);
    }

    public function emisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'emitido_por');
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(DocumentoEvento::class)->latest();
    }

    public function scopeConRelaciones(Builder $query): Builder
    {
        return $query->with(['area', 'tipo', 'emisor']);
    }

    public function scopeFiltrar(Builder $query, array $filtros): Builder
    {
        return $query
            ->when($filtros['buscar'] ?? null, function (Builder $query, string $buscar): void {
                $query->where(function (Builder $query) use ($buscar): void {
                    $patron = '%'.mb_strtolower($buscar).'%';

                    $query
                        ->whereRaw('LOWER(COALESCE(cite, ?)) LIKE ?', ['', $patron])
                        ->orWhereRaw('LOWER(asunto) LIKE ?', [$patron])
                        ->orWhereRaw('LOWER(destinatario) LIKE ?', [$patron]);
                });
            })
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
}
