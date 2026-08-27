<?php

namespace Database\Seeders;

use App\Models\Area;
use Illuminate\Database\Seeder;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            ['codigo' => 'ADM', 'nombre' => 'Gerencia, Notas Administrativas'],
            ['codigo' => 'SIS', 'nombre' => 'Sistemas, Soporte Técnico'],
            ['codigo' => 'DEV', 'nombre' => 'Desarrollo de Aplicaciones/Páginas Web'],
            ['codigo' => 'AUD', 'nombre' => 'Auditoría Informática'],
            ['codigo' => 'CNT', 'nombre' => 'Contabilidad'],
        ];

        foreach ($areas as $area) {
            Area::query()->updateOrCreate(
                ['codigo' => $area['codigo']],
                [...$area, 'activo' => true],
            );
        }
    }
}

