<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\Area;
use App\Models\Documento;
use App\Models\Empresa;
use App\Models\Tipo;
use App\Models\User;
use App\Services\GeneradorArchivosLaravel;
use App\Services\GuardarDocumentoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use Tests\TestCase;

final class MembreteDocumentoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Area $area;
    private Tipo $tipo;
    private Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('sdaya.documentos.disk', 'local'));

        $this->empresa = Empresa::query()->create([
            'nombre' => 'Tecnología Andina S.R.L.',
            'nombre_comercial' => 'Andina Tech',
            'nombre_aplicacion' => 'Andina Doc',
            'nit' => '1029384756',
            'direccion' => 'Av. Arce #100, La Paz',
            'telefono' => '+591 2 2445566',
            'correo' => 'contacto@andina.bo',
            'sitio_web' => 'https://andina.bo',
            'color_principal' => '#1b2a4a',
            'color_secundario' => '#3a7bd5',
            'logo' => 'identidad/logo_prueba.png',
            'logo_documentos' => 'identidad/membrete_oficial_prueba.png',
            'activo' => true,
            'es_predeterminada' => true,
        ]);

        $disk = Storage::disk(config('sdaya.documentos.disk', 'local'));
        $disk->put('identidad/logo_prueba.png', 'fake-logo-bytes');
        $disk->put('identidad/membrete_oficial_prueba.png', 'fake-membrete-bytes');

        $this->admin = User::factory()->administrador()->create([
            'empresa_id' => $this->empresa->id,
        ]);

        $this->area = Area::query()->create([
            'codigo' => 'DIR',
            'nombre' => 'Dirección General',
            'activo' => true,
        ]);

        $this->tipo = Tipo::query()->create([
            'codigo' => 'NOT',
            'nombre' => 'Nota Oficial',
            'activo' => true,
        ]);
    }

    public function test_nuevo_documento_guarda_logo_documentos_en_datos_empresa(): void
    {
        $guardarService = app(GuardarDocumentoService::class);

        $documento = $guardarService->crear([
            'area_id' => $this->area->id,
            'tipo_id' => $this->tipo->id,
            'fecha_documento' => '2026-09-10',
            'lugar' => 'La Paz',
            'alineacion_encabezado' => 'right',
            'alineacion_pie_firma' => 'right',
            'firmante_id' => $this->admin->id,
            'empresa_id' => $this->empresa->id,
            'asunto' => 'Prueba de membrete institucional',
            'contenido' => '<p>Cuerpo del documento de prueba con membrete.</p>',
        ], $this->admin);

        $this->assertNotNull($documento->datos_empresa);
        $this->assertSame('identidad/membrete_oficial_prueba.png', $documento->datos_empresa['logo_documentos']);

        $datosEmpresa = $documento->datosEmpresa();
        $this->assertSame('identidad/membrete_oficial_prueba.png', $datosEmpresa['logo_documentos']);
        $this->assertNotNull($documento->membreteDataUri());
    }

    public function test_documento_historico_sin_logo_documentos_obtiene_fallback_de_empresa(): void
    {
        $documento = Documento::query()->create([
            'cite' => 'DIR-NOT-2026/001',
            'anio' => 2026,
            'secuencia' => 1,
            'area_id' => $this->area->id,
            'tipo_id' => $this->tipo->id,
            'firmante_id' => $this->admin->id,
            'empresa_id' => $this->empresa->id,
            'datos_empresa' => [
                'id' => $this->empresa->id,
                'nombre' => 'Tecnología Andina S.R.L.',
                'nit' => '1029384756',
                'logo' => 'identidad/logo_prueba.png',
            ],
            'estado' => \App\Enums\EstadoDocumento::BORRADOR,
            'fecha_documento' => '2026-09-10',
            'asunto' => 'Documento anterior',
            'contenido' => '<p>Contenido histórico</p>',
            'cuerpo_hash' => hash('sha256', '<p>Contenido histórico</p>'),
        ]);

        $datosEmpresa = $documento->datosEmpresa();
        $this->assertSame('identidad/membrete_oficial_prueba.png', $datosEmpresa['logo_documentos']);
        $this->assertNotNull($documento->membreteDataUri());
    }

    public function test_generador_archivos_prioriza_logo_documentos_sobre_logo_regular(): void
    {
        $documento = Documento::query()->create([
            'cite' => 'DIR-NOT-2026/002',
            'anio' => 2026,
            'secuencia' => 2,
            'area_id' => $this->area->id,
            'tipo_id' => $this->tipo->id,
            'firmante_id' => $this->admin->id,
            'empresa_id' => $this->empresa->id,
            'datos_empresa' => [
                'id' => $this->empresa->id,
                'nombre' => 'Tecnología Andina S.R.L.',
                'nit' => '1029384756',
                'logo' => 'identidad/logo_prueba.png',
                'logo_documentos' => 'identidad/membrete_oficial_prueba.png',
            ],
            'estado' => \App\Enums\EstadoDocumento::BORRADOR,
            'fecha_documento' => '2026-09-10',
            'asunto' => 'Prueba generador',
            'contenido' => '<p>Contenido para generar</p>',
            'cuerpo_hash' => hash('sha256', '<p>Contenido para generar</p>'),
        ]);

        $generador = app(GeneradorArchivosLaravel::class);
        $metodo = new ReflectionMethod($generador, 'resolverMembreteRuta');
        $metodo->setAccessible(true);

        $rutaResuelta = $metodo->invoke($generador, $documento);

        $disk = Storage::disk(config('sdaya.documentos.disk', 'local'));
        $this->assertSame($disk->path('identidad/membrete_oficial_prueba.png'), $rutaResuelta);
    }

    public function test_si_empresa_no_tiene_membrete_no_usa_logo_regular_y_emite_hoja_en_blanco(): void
    {
        $this->empresa->update(['logo_documentos' => null]);

        $documento = Documento::query()->create([
            'cite' => 'DIR-NOT-2026/003',
            'anio' => 2026,
            'secuencia' => 3,
            'area_id' => $this->area->id,
            'tipo_id' => $this->tipo->id,
            'firmante_id' => $this->admin->id,
            'empresa_id' => $this->empresa->id,
            'datos_empresa' => [
                'id' => $this->empresa->id,
                'nombre' => 'Tecnología Andina S.R.L.',
                'nit' => '1029384756',
                'logo' => 'identidad/logo_prueba.png',
                'logo_documentos' => null,
            ],
            'estado' => \App\Enums\EstadoDocumento::BORRADOR,
            'fecha_documento' => '2026-09-10',
            'asunto' => 'Prueba hoja en blanco',
            'contenido' => '<p>Documento sin membrete</p>',
            'cuerpo_hash' => hash('sha256', '<p>Documento sin membrete</p>'),
        ]);

        $generador = app(GeneradorArchivosLaravel::class);
        $metodo = new ReflectionMethod($generador, 'resolverMembreteRuta');
        $metodo->setAccessible(true);

        $rutaResuelta = $metodo->invoke($generador, $documento);

        $this->assertNull($rutaResuelta, 'Si se eliminó el membrete, debe devolver null (hoja en blanco) y no el logo de la empresa.');
        $this->assertNull($documento->membreteDataUri());
    }

    public function test_vista_show_mantiene_formato_limpio_sin_banner_de_membrete_innecesario(): void
    {
        $documento = Documento::query()->create([
            'cite' => 'DIR-NOT-2026/004',
            'anio' => 2026,
            'secuencia' => 4,
            'area_id' => $this->area->id,
            'tipo_id' => $this->tipo->id,
            'firmante_id' => $this->admin->id,
            'empresa_id' => $this->empresa->id,
            'datos_empresa' => [
                'id' => $this->empresa->id,
                'nombre' => 'Tecnología Andina S.R.L.',
                'logo_documentos' => 'identidad/membrete_oficial_prueba.png',
            ],
            'estado' => \App\Enums\EstadoDocumento::BORRADOR,
            'fecha_documento' => '2026-09-10',
            'asunto' => 'Carta vista limpia',
            'contenido' => '<p>Contenido limpio</p>',
            'cuerpo_hash' => hash('sha256', '<p>Contenido limpio</p>'),
        ]);

        $response = $this->actingAs($this->admin)->get(route('documentos.show', $documento));

        $response->assertOk();
        $response->assertDontSee('data-membrete-preview', false);
        $response->assertSee('Contenido del documento');
    }
}
