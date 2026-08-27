<?php

declare(strict_types=1);

namespace App\Enums;

enum RolUsuario: string
{
    case ADMIN = 'admin';
    case EDITOR = 'editor';
    case LECTOR = 'lector';

    public function etiqueta(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrador',
            self::EDITOR => 'Editor',
            self::LECTOR => 'Lector',
        };
    }

    public function puedeEditarDocumentos(): bool
    {
        return in_array($this, [self::ADMIN, self::EDITOR], true);
    }

    public function esAdministrador(): bool
    {
        return $this === self::ADMIN;
    }
}
