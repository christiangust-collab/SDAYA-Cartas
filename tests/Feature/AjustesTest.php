<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EstadoDocumento;
use App\Enums\RolUsuario;
use App\Models\Area;
use App\Models\Documento;
use App\Models\Empresa;
use App\Models\Tipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class AjustesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('sdaya.documentos.disk', 'local'));

        $this->admin = User::factory()->administrador()->create();
        $this->editor = User::factory()->editor()->create();
    }

    public function test_instalacion_sin_empresas_tiene_fallback_neutral(): void
    {
        Empresa::query()->delete();

        $empresaActual = Empresa::actual();

        $this->assertSame('Empresa Demo', $empresaActual->nombre);
        $this->assertSame('Gestión de Cartas', $empresaActual->nombre_aplicacion);
        $this->assertSame('#002b49', $empresaActual->color_principal);
        $this->assertSame('#00487a', $empresaActual->color_secundario);
    }

    public function test_usuario_no_administrador_no_puede_acceder_a_ajustes(): void
    {
        $response = $this->actingAs($this->editor)->get(route('ajustes.index'));
        $response->assertForbidden();

        $responsePut = $this->actingAs($this->editor)->put(route('ajustes.update'), [
            'nombre' => 'Intento Hack',
            'nombre_aplicacion' => 'Hack App',
            'color_principal' => '#112233',
            'color_secundario' => '#445566',
        ]);
        $responsePut->assertForbidden();
    }

    public function test_administrador_puede_ver_pantalla_de_ajustes(): void
    {
        $response = $this->actingAs($this->admin)->get(route('ajustes.index'));

        $response->assertOk();
        $response->assertSee('Configuración de Empresa');
        $response->assertSee('Color Principal');
        $response->assertSee('Color Secundario');
        $response->assertSee('Guardar Ajustes');
    }

    public function test_administrador_puede_actualizar_identidad_y_colores(): void
    {
        $response = $this->actingAs($this->admin)->put(route('ajustes.update'), [
            'nombre' => 'Constructora Horizonte S.R.L.',
            'nombre_comercial' => 'Horizonte',
            'nombre_aplicacion' => 'Horizonte Documental',
            'nit' => '8827364510',
            'direccion' => 'Av. Principal #500',
            'telefono' => '71512345',
            'correo' => 'info@horizonte.com',
            'sitio_web' => 'https://horizonte.com',
            'color_principal' => '#0f172a',
            'color_secundario' => '#0284c7',
        ]);

        $response->assertRedirect(route('ajustes.index'));
        $response->assertSessionHas('success');

        $empresaActual = Empresa::actual();
        $this->assertSame('Constructora Horizonte S.R.L.', $empresaActual->nombre);
        $this->assertSame('Horizonte Documental', $empresaActual->nombre_aplicacion);
        $this->assertSame('#0f172a', $empresaActual->color_principal);
        $this->assertSame('#0284c7', $empresaActual->color_secundario);
    }

    public function test_colores_con_formato_invalido_son_rechazados_por_seguridad(): void
    {
        $response = $this->actingAs($this->admin)->put(route('ajustes.update'), [
            'nombre' => 'Empresa Test',
            'nombre_aplicacion' => 'Cartas Test',
            'color_principal' => 'rgb(255,0,0); background: url(evil)',
            'color_secundario' => '#invalid',
        ]);

        $response->assertSessionHasErrors(['color_principal', 'color_secundario']);
    }

    public function test_administrador_puede_subir_logo_y_favicon_y_eliminarlos(): void
    {
        $logo = UploadedFile::fake()->image('logo_corporativo.png', 300, 100);
        $favicon = UploadedFile::fake()->image('favicon_ico.png', 32, 32);

        $response = $this->actingAs($this->admin)->put(route('ajustes.update'), [
            'nombre' => 'Soluciones Andinas S.A.',
            'nombre_aplicacion' => 'Cartas Andinas',
            'color_principal' => '#1a365d',
            'color_secundario' => '#2b6cb0',
            'logo' => $logo,
            'favicon' => $favicon,
        ]);

        $response->assertRedirect(route('ajustes.index'));

        $empresa = Empresa::actual();
        $this->assertNotNull($empresa->logo);
        $this->assertNotNull($empresa->favicon);

        $disk = Storage::disk(config('sdaya.documentos.disk', 'local'));
        $disk->assertExists($empresa->logo);
        $disk->assertExists($empresa->favicon);

        // Ahora eliminar logo y favicon
        $responseDelete = $this->actingAs($this->admin)->put(route('ajustes.update'), [
            'nombre' => 'Soluciones Andinas S.A.',
            'nombre_aplicacion' => 'Cartas Andinas',
            'color_principal' => '#1a365d',
            'color_secundario' => '#2b6cb0',
            'eliminar_logo' => '1',
            'eliminar_favicon' => '1',
        ]);

        $responseDelete->assertRedirect(route('ajustes.index'));

        $empresa->refresh();
        $this->assertNull($empresa->logo);
        $this->assertNull($empresa->favicon);
    }

    public function test_pagina_de_verificacion_es_dinamica_y_preserva_empresa_historica(): void
    {
        $area = Area::query()->create(['codigo' => 'DIR', 'nombre' => 'Dirección', 'activo' => true]);
        $tipo = Tipo::query()->create(['codigo' => 'NOT', 'nombre' => 'Nota', 'activo' => true]);

        // Empresa 1 emite la carta
        $empresaOriginal = Empresa::query()->create([
            'nombre' => 'Empresa Alfa S.R.L.',
            'nombre_comercial' => 'Alfa',
            'nombre_aplicacion' => 'Alfa Documentos',
            'color_principal' => '#112233',
            'color_secundario' => '#445566',
            'activo' => true,
            'es_predeterminada' => true,
        ]);

        $documento = Documento::query()->create([
            'cite' => 'ALFA-DIR-2026/001',
            'anio' => 2026,
            'secuencia' => 1,
            'area_id' => $area->id,
            'tipo_id' => $tipo->id,
            'firmante_id' => $this->admin->id,
            'empresa_id' => $empresaOriginal->id,
            'datos_empresa' => [
                'id' => $empresaOriginal->id,
                'nombre' => 'Empresa Alfa S.R.L.',
                'nit' => '1122334455',
            ],
            'estado' => EstadoDocumento::EMITIDO,
            'fecha_documento' => now()->toDateString(),
            'emitido_at' => now(),
            'destinatario_titulo' => 'Lic.',
            'destinatario_nombre' => 'Carlos López',
            'destinatario_cargo' => 'Gerente',
            'asunto' => 'Informe técnico inicial',
            'contenido' => '<p>Contenido oficial</p>',
            'cuerpo_hash' => hash('sha256', '<p>Contenido oficial</p>'),
            'hash_verificacion' => str_repeat('a', 64),
            'ruta_pdf' => 'documentos/2026/ALFA-DIR-2026-001.pdf',
            'hash_pdf' => hash('sha256', 'fake-pdf-bytes'),
            'ruta_docx' => 'documentos/2026/ALFA-DIR-2026-001.docx',
            'ruta_qr' => 'documentos/2026/ALFA-DIR-2026-001-qr.png',
        ]);

        Storage::disk(config('sdaya.documentos.disk', 'local'))->put('documentos/2026/ALFA-DIR-2026-001.pdf', 'fake-pdf-bytes');

        // Verificar con la empresa original
        $responseVerif = $this->get(route('verificar.show', $documento->hash_verificacion));
        $responseVerif->assertOk();
        $responseVerif->assertSee('Empresa Alfa S.R.L.');

        // Ahora el sistema cambia su identidad activa a Empresa Beta
        $empresaOriginal->update(['es_predeterminada' => false]);
        Empresa::query()->create([
            'nombre' => 'Empresa Beta Corp',
            'nombre_comercial' => 'Beta',
            'nombre_aplicacion' => 'Beta Cartas',
            'color_principal' => '#990000',
            'color_secundario' => '#cc0000',
            'activo' => true,
            'es_predeterminada' => true,
        ]);

        // La carta histórica de Alfa NO debe mostrarse como Beta
        $responseVerif2 = $this->get(route('verificar.show', $documento->hash_verificacion));
        $responseVerif2->assertOk();
        $responseVerif2->assertSee('Empresa Alfa S.R.L.');
        $responseVerif2->assertDontSee('Empresa Beta Corp');
    }

    public function test_administrador_puede_subir_membrete_en_formato_pdf_y_se_convierte_a_png(): void
    {
        $pdfContenido = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Count 1/Kids[3 0 R]>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R>>endobj\nxref\n0 4\n0000000000 65535 f\n0000000010 00000 n\n0000000053 00000 n\n0000000102 00000 n\ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n178\n%%EOF\n";
        $pdfFile = UploadedFile::fake()->createWithContent('membrete_oficial.pdf', $pdfContenido);

        $response = $this->actingAs($this->admin)->put(route('ajustes.update'), [
            'nombre' => 'Consultora Andina S.R.L.',
            'nombre_aplicacion' => 'Andina Cartas',
            'color_principal' => '#1a365d',
            'color_secundario' => '#2b6cb0',
            'logo_documentos' => $pdfFile,
        ]);

        $response->assertRedirect(route('ajustes.index'));
        $response->assertSessionHas('success');

        $empresa = Empresa::actual();
        $this->assertNotNull($empresa->logo_documentos);
        $this->assertStringEndsWith('.png', $empresa->logo_documentos);

        $disk = Storage::disk(config('sdaya.documentos.disk', 'local'));
        $disk->assertExists($empresa->logo_documentos);
    }
}