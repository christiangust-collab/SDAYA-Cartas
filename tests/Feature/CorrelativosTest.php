<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Tipo;
use App\Services\CiteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

final class CorrelativosTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_vista_previa_no_reserva_un_correlativo(): void
    {
        $area = Area::factory()->create(['codigo' => 'DEV']);
        $tipo = Tipo::factory()->create(['codigo' => 'COT']);
        $servicio = app(CiteService::class);

        $this->assertSame('SD-DEV-COT-2026/001', $servicio->vistaPrevia($area, $tipo, 2026));
        $this->assertDatabaseCount('series_correlativos', 0);
    }

    public function test_cada_serie_incrementa_de_forma_independiente(): void
    {
        $area = Area::factory()->create(['codigo' => 'DEV']);
        $cotizacion = Tipo::factory()->create(['codigo' => 'COT']);
        $nota = Tipo::factory()->create(['codigo' => 'NE']);
        $servicio = app(CiteService::class);

        $primero = DB::transaction(fn () => $servicio->reservarSiguiente($area, $cotizacion, 2026));
        $segundo = DB::transaction(fn () => $servicio->reservarSiguiente($area, $cotizacion, 2026));
        $otraSerie = DB::transaction(fn () => $servicio->reservarSiguiente($area, $nota, 2026));

        $this->assertSame('SD-DEV-COT-2026/001', $primero->cite);
        $this->assertSame('SD-DEV-COT-2026/002', $segundo->cite);
        $this->assertSame('SD-DEV-NE-2026/001', $otraSerie->cite);
    }

    public function test_no_se_puede_reservar_fuera_de_una_transaccion(): void
    {
        $area = Area::factory()->create();
        $tipo = Tipo::factory()->create();

        $conexion = DB::connection();

        while ($conexion->transactionLevel() > 0) {
            $conexion->rollBack();
        }

        $this->expectException(LogicException::class);

        app(CiteService::class)->reservarSiguiente($area, $tipo, 2026);
    }
}
