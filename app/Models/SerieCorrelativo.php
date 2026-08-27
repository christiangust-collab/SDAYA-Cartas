<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SerieCorrelativo extends Model
{
    use HasFactory;

    protected $table = 'series_correlativos';

    protected $fillable = [
        'area_id',
        'tipo_id',
        'anio',
        'ultimo_correlativo',
    ];

    protected function casts(): array
    {
        return [
            'anio' => 'integer',
            'ultimo_correlativo' => 'integer',
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
}
