<?php

namespace Tests\Unit;

use App\Enums\EstadoDocumento;
use App\Enums\RolUsuario;
use PHPUnit\Framework\TestCase;

class EnumsTest extends TestCase
{
    public function test_los_valores_de_dominio_permanecen_estables(): void
    {
        $this->assertSame('admin', RolUsuario::ADMIN->value);
        $this->assertSame('editor', RolUsuario::EDITOR->value);
        $this->assertSame('borrador', EstadoDocumento::BORRADOR->value);
        $this->assertSame('emitido', EstadoDocumento::EMITIDO->value);
        $this->assertSame('anulado', EstadoDocumento::ANULADO->value);
    }
}

