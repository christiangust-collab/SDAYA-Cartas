<?php

declare(strict_types=1);

namespace App\Enums;

enum RolUsuario: string
{
    case ADMIN = 'admin';
    case EDITOR = 'editor';

    public function etiqueta(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrador',
            self::EDITOR => 'Editor',
        };
    }

    public function puedeEditarDocumentos(): bool
    {
        return true;
    }

    public function esAdministrador(): bool
    {
        return $this === self::ADMIN;
    }
}
