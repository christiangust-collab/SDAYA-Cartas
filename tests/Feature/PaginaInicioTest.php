<?php

namespace Tests\Feature;

use App\Models\Empresa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginaInicioTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_pagina_inicial_responde_correctamente(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Gestión documental');
    }

    public function test_la_pagina_inicial_muestra_nombre_de_empresa_configurada(): void
    {
        Empresa::query()->create([
            'nombre' => 'SDAYA S.R.L.',
            'nombre_comercial' => 'SDAYA',
            'nombre_aplicacion' => 'SDAYA Cartas',
            'activo' => true,
            'es_predeterminada' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('SDAYA Cartas');
    }

    public function test_la_pagina_inicial_aplica_colores_y_sigla_de_empresa_personalizada(): void
    {
        Empresa::query()->update(['es_predeterminada' => false]);

        Empresa::query()->create([
            'nombre' => 'CCNC SRL',
            'nombre_comercial' => 'CCNC',
            'nombre_aplicacion' => 'Gestión Documental CCNC',
            'color_principal' => '#b91c1c', // Rojo
            'color_secundario' => '#f59e0b', // Amarillo / Ámbar
            'activo' => true,
            'es_predeterminada' => true,
        ]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Gestión Documental CCNC')
            ->assertSee('--brand-primary: #b91c1c', false)
            ->assertSee('--brand-secondary: #f59e0b', false)
            ->assertSee('CCNC-ADM-NE-', false)
            ->assertSee('Verificada por CCNC', false);
    }
}

