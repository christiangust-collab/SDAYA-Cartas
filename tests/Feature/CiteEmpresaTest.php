<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\Area;
use App\Models\Documento;
use App\Models\Empresa;
use App\Models\Tipo;
use App\Models\User;
use App\Services\CiteService;
use App\Services\EmitirDocumentoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CiteEmpresaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Area $area;
    private Tipo $tipo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->administrador()->create();
        $this->area = Area::query()->create(['codigo' => 'CNT', 'nombre' => 'Contabilidad', 'activo' => true]);
        $this->tipo = Tipo::query()->create(['codigo' => 'NE', 'nombre' => 'Nota Externa', 'activo' => true]);
    }

    public function test_cite_toma_sigla_de_nombre_comercial_en_mayusculas(): void
    {
        $empresa = Empresa::query()->create([
            'nombre' => 'CCNC SRL',
            'nombre_comercial' => 'ccnc',
            'activo' => true,
            'es_predeterminada' => true,
        ]);

        $this->assertSame('CCNC', $empresa->siglaCite());

        $cites = app(CiteService::class);
        $preview = $cites->vistaPrevia($this->area, $this->tipo, 2026, $empresa);

        $this->assertSame('CCNC-CNT-NE-2026/001', $preview);
    }

    public function test_cite_usa_sd_para_empresa_sdaya(): void
    {
        $empresa = Empresa::query()->create([
            'nombre' => 'SDAYA S.R.L.',
            'nombre_comercial' => 'SDAYA',
            'activo' => true,
            'es_predeterminada' => true,
        ]);

        $this->assertSame('SD', $empresa->siglaCite());

        $cites = app(CiteService::class);
        $preview = $cites->vistaPrevia($this->area, $this->tipo, 2026, $empresa);

        $this->assertSame('SD-CNT-NE-2026/001', $preview);
    }

    public function test_emision_de_documento_utiliza_sigla_de_empresa_emisora(): void
    {
        $empresa = Empresa::query()->create([
            'nombre' => 'Constructora Horizonte S.R.L.',
            'nombre_comercial' => 'HORIZONTE',
            'activo' => true,
            'es_predeterminada' => true,
        ]);

        $usuario = User::factory()->editor()->create(['empresa_id' => $empresa->id]);

        $documento = Documento::query()->create([
            'area_id' => $this->area->id,
            'tipo_id' => $this->tipo->id,
            'empresa_id' => $empresa->id,
            'firmante_id' => $usuario->id,
            'anio' => 2026,
            'fecha_documento' => '2026-09-10',
            'asunto' => 'Documento con sigla propia',
            'contenido' => '<p>Cuerpo del documento</p>',
            'cuerpo_hash' => hash('sha256', '<p>Cuerpo del documento</p>'),
            'estado' => \App\Enums\EstadoDocumento::BORRADOR,
        ]);

        $emitirService = app(EmitirDocumentoService::class);
        $emitido = $emitirService->emitir($documento, $usuario);

        $this->assertSame('HORIZONTE-CNT-NE-2026/001', $emitido->cite);
    }
}