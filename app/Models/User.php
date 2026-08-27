<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RolUsuario;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

final class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
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
        ];
    }

    public function documentosEmitidos(): HasMany
    {
        return $this->hasMany(Documento::class, 'emitido_por');
    }

    public function eventosDocumento(): HasMany
    {
        return $this->hasMany(DocumentoEvento::class);
    }

    public function esAdministrador(): bool
    {
        return $this->role === RolUsuario::ADMIN;
    }

    public function puedeEditarDocumentos(): bool
    {
        return $this->role->puedeEditarDocumentos();
    }
}
