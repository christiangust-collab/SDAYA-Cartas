<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Tipo extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'activo',
    ];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    protected function codigo(): Attribute
    {
        return Attribute::make(
            set: static fn (string $value): string => mb_strtoupper(trim($value)),
        );
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(Documento::class);
    }

    public function documentosEmitidos(): HasMany
    {
        return $this->documentos()->whereNotNull('cite');
    }
}
