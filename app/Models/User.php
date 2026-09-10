<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RolUsuario;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

final class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'activo',
        'empresa_id',
        'cargo',
        'empresa',
        'telefono',
        'firma_digital',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => RolUsuario::class,
            'activo' => 'boolean',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->empresaInstitucion();
    }

    public function empresaInstitucion(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function documentosEmitidos(): HasMany
    {
        return $this->hasMany(Documento::class, 'emitido_por');
    }

    public function documentosFirmados(): HasMany
    {
        return $this->hasMany(Documento::class, 'firmante_id');
    }

    public function eventosDocumento(): HasMany
    {
        return $this->hasMany(DocumentoEvento::class);
    }

    /** @return array{nombre: string, cargo: string, empresa: string, correo: string, telefono: string, firma_digital: ?string} */
    public function datosPieFirma(): array
    {
        $nombreEmpresa = $this->empresaInstitucion?->nombre
            ?: (is_string($this->empresa ?? null) && filled($this->empresa) ? $this->empresa : 'SDAYA S.R.L.');

        return [
            'nombre' => $this->name,
            'cargo' => (string) ($this->cargo ?? ''),
            'empresa' => (string) $nombreEmpresa,
            'correo' => (string) $this->email,
            'telefono' => (string) ($this->telefono ?? ''),
            'firma_digital' => $this->firma_digital ? (string) $this->firma_digital : null,
        ];
    }

    public function firmaDataUri(): ?string
    {
        if (! filled($this->firma_digital)) {
            return null;
        }

        $disco = Storage::disk(config('sdaya.documentos.disk', 'local'));
        if (! $disco->exists((string) $this->firma_digital)) {
            return null;
        }

        $contenido = $disco->get((string) $this->firma_digital);
        $mime = $disco->mimeType((string) $this->firma_digital) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($contenido);
    }

    public function esAdministrador(): bool
    {
        return $this->role === RolUsuario::ADMIN;
    }

    public function estaActivo(): bool
    {
        return (bool) ($this->activo ?? true);
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function puedeEditarDocumentos(): bool
    {
        return $this->role->puedeEditarDocumentos();
    }
}
