<?php

namespace Database\Seeders;

use App\Models\Tipo;
use Illuminate\Database\Seeder;

class TipoSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            [
                'codigo' => 'NI',
                'nombre' => 'Nota Interna',
                'descripcion' => 'Comunicados al personal, requerimientos de compras internas y memorándums.',
            ],
            [
                'codigo' => 'NE',
                'nombre' => 'Nota Externa',
                'descripcion' => 'Cartas formales a clientes, Impuestos, proveedores y entidades públicas.',
            ],
            [
                'codigo' => 'COT',
                'nombre' => 'Cotización',
                'descripcion' => 'Propuestas técnicas y económicas de servicios.',
            ],
        ];

        foreach ($tipos as $tipo) {
            Tipo::query()->updateOrCreate(
                ['codigo' => $tipo['codigo']],
                [...$tipo, 'activo' => true],
            );
        }
    }
}

