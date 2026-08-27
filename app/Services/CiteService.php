<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\CiteAsignado;
use App\Models\Area;
use App\Models\SerieCorrelativo;
use App\Models\Tipo;
use Illuminate\Support\Facades\DB;
use LogicException;

final class CiteService
{
    public function vistaPrevia(Area $area, Tipo $tipo, int $anio): string
    {
        $ultimo = (int) (SerieCorrelativo::query()
            ->whereBelongsTo($area)
            ->whereBelongsTo($tipo)
            ->where('anio', $anio)
            ->value('ultimo_correlativo') ?? 0);

        return $this->formatear($area, $tipo, $anio, $ultimo + 1);
    }

    public function reservarSiguiente(Area $area, Tipo $tipo, int $anio): CiteAsignado
    {
        if (DB::transactionLevel() < 1) {
            throw new LogicException('La reserva del CITE debe ejecutarse dentro de una transacción.');
        }

        $ahora = now();

        DB::table('series_correlativos')->insertOrIgnore([
            'area_id' => $area->getKey(),
            'tipo_id' => $tipo->getKey(),
            'anio' => $anio,
            'ultimo_correlativo' => 0,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ]);

        $serie = SerieCorrelativo::query()
            ->whereBelongsTo($area)
            ->whereBelongsTo($tipo)
            ->where('anio', $anio)
            ->lockForUpdate()
            ->firstOrFail();

        $siguiente = $serie->ultimo_correlativo + 1;
        $serie->update(['ultimo_correlativo' => $siguiente]);

        return new CiteAsignado(
            cite: $this->formatear($area, $tipo, $anio, $siguiente),
            correlativo: $siguiente,
        );
    }

    private function formatear(Area $area, Tipo $tipo, int $anio, int $correlativo): string
    {
        return sprintf(
            'SD-%s-%s-%d/%03d',
            $area->codigo,
            $tipo->codigo,
            $anio,
            $correlativo,
        );
    }
}
