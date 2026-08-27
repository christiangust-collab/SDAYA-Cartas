<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventoDocumento;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DocumentoEvento extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'documento_id',
        'user_id',
        'evento',
        'datos',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'evento' => EventoDocumento::class,
            'datos' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }

    public function documento(): BelongsTo
    {
        return $this->belongsTo(Documento::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
