<?php

declare(strict_types=1);

namespace App\Enums;

enum EventoDocumento: string
{
    case CREADO = 'creado';
    case ACTUALIZADO = 'actualizado';
    case EMITIDO = 'emitido';
    case ANULADO = 'anulado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::CREADO => 'Borrador creado',
            self::ACTUALIZADO => 'Borrador actualizado',
            self::EMITIDO => 'Documento emitido',
            self::ANULADO => 'Documento anulado',
        };
    }
}
