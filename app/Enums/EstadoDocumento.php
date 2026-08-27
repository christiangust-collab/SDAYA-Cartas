<?php

declare(strict_types=1);

namespace App\Enums;

enum EstadoDocumento: string
{
    case BORRADOR = 'borrador';
    case EMITIDO = 'emitido';
    case ANULADO = 'anulado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::BORRADOR => 'Borrador',
            self::EMITIDO => 'Emitido',
            self::ANULADO => 'Anulado',
        };
    }

    public function permiteEdicion(): bool
    {
        return $this === self::BORRADOR;
    }

    public function estaPublicado(): bool
    {
        return in_array($this, [self::EMITIDO, self::ANULADO], true);
    }
}
