<?php

namespace Tests\Feature;

use Tests\TestCase;

class PaginaInicioTest extends TestCase
{
    public function test_la_pagina_inicial_responde_correctamente(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('SDAYA Cartas');
    }
}

