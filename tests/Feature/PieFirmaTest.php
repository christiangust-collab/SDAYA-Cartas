<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\GeneradorArchivosDocumento;
use App\Data\ArchivosGenerados;
use App\Enums\EstadoDocumento;
use App\Models\Area;
use App\Models\Documento;
use App\Models\Tipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class PieFirmaTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_puede_actualizar_sus_datos_de_pie_de_firma_en_su_perfil(): void
    {
        $usuario = User::factory()->editor()->create([
            'name' => 'Lic. Carlos Medina',
            'cargo' => 'Auditor Junior',
            'empresa' => 'SDAYA S.R.L.',
            'telefono' => '+591 71111111',
        ]);

        $response = $this->actingAs($usuario)->put(route('perfil.update'), [
            'name' => 'Lic. Carlos Medina Flores',
            'cargo' => 'Gerente de Auditoría y Control',
            'empresa' => 'SDAYA Consultores S.R.L.',
            'telefono' => '+591 72222222',
        ]);

        $response->assertRedirect(route('perfil.edit'));
        $response->assertSessionHas('success');

        $usuario->refresh();
        $this->assertSame('Lic. Carlos Medina Flores', $usuario->name);
        $this->assertSame('Gerente de Auditoría y Control', $usuario->cargo);
        $this->assertSame('SDAYA Consultores S.R.L.', $usuario->empresa);
        $this->assertSame('+591 72222222', $usuario->telefono);
    }

    public function test_creacion_de_carta_asigna_firmante_y_captura_datos_de_pie_de_firma(): void
    {
        $firmante = User::factory()->editor()->create([
            'name' => 'Ing. Juan Pérez Rojas',
            'email' => 'juan.perez@empresa.com',
            'cargo' => 'Gerente Administrativo',
            'empresa' => 'SDAYA S.R.L.',
            'telefono' => '+591 79998888',
        ]);
        $area = Area::factory()->create(['codigo' => 'ADM']);
        $tipo = Tipo::factory()->create(['codigo' => 'NE']);

        $response = $this->actingAs($firmante)->post(route('documentos.store'), [
            'area_id' => $area->id,
            'tipo_id' => $tipo->id,
            'fecha_documento' => '2026-09-01',
            'lugar' => 'La Paz',
            'asunto' => 'Solicitud de informe trimestral',
            'destinatario' => 'Lic. Alberto Ramos',
            'firmante_id' => $firmante->id,
            'contenido' => '<p>Por medio de la presente solicitamos el informe.</p>',
            'accion' => 'guardar',
        ]);

        $documento = Documento::query()->sole();
        $response->assertRedirect(route('documentos.show', $documento));

        $this->assertSame($firmante->id, $documento->firmante_id);
        $this->assertIsArray($documento->datos_firmante);
        $this->assertSame('Ing. Juan Pérez Rojas', $documento->datos_firmante['nombre']);
        $this->assertSame('Gerente Administrativo', $documento->datos_firmante['cargo']);
        $this->assertSame('SDAYA S.R.L.', $documento->datos_firmante['empresa']);
        $this->assertSame('juan.perez@empresa.com', $documento->datos_firmante['correo']);
        $this->assertSame('+591 79998888', $documento->datos_firmante['telefono']);
    }

    public function test_vista_previa_muestra_pie_de_firma_institucional(): void
    {
        $firmante = User::factory()->editor()->create([
            'name' => 'Ing. Juan Pérez Rojas',
            'email' => 'juan.perez@empresa.com',
            'cargo' => 'Gerente Administrativo',
            'empresa' => 'SDAYA S.R.L.',
            'telefono' => '+591 79998888',
        ]);
        $area = Area::factory()->create();
        $tipo = Tipo::factory()->create();

        $documento = Documento::factory()->create([
            'area_id' => $area->id,
            'tipo_id' => $tipo->id,
            'firmante_id' => $firmante->id,
            'datos_firmante' => $firmante->datosPieFirma(),
            'estado' => EstadoDocumento::BORRADOR,
        ]);

        $response = $this->actingAs($firmante)->get(route('documentos.preview', $documento));

        $response->assertOk();
        $response->assertSee('Ing. Juan Pérez Rojas');
        $response->assertSee('Gerente Administrativo');
        $response->assertSee('SDAYA S.R.L.');
        $response->assertSee('juan.perez@empresa.com');
        $response->assertSee('+591 79998888');
    }

    public function test_emision_sella_pie_de_firma_e_incluye_en_documento(): void
    {
        Storage::fake('local');
        config()->set('sdaya.documentos.disk', 'local');

        $firmante = User::factory()->editor()->create([
            'name' => 'Dra. Elena Vargas',
            'email' => 'elena.vargas@sdaya.com',
            'cargo' => 'Directora Ejecutiva',
            'empresa' => 'SDAYA S.R.L.',
            'telefono' => '+591 75554433',
        ]);
        $area = Area::factory()->create(['codigo' => 'DIR']);
        $tipo = Tipo::factory()->create(['codigo' => 'OF']);

        $documento = Documento::factory()->create([
            'area_id' => $area->id,
            'tipo_id' => $tipo->id,
            'firmante_id' => $firmante->id,
            'datos_firmante' => $firmante->datosPieFirma(),
            'estado' => EstadoDocumento::BORRADOR,
        ]);

        $this->actingAs($firmante)->post(route('documentos.emitir', $documento));

        $documento->refresh();
        $this->assertSame(EstadoDocumento::EMITIDO, $documento->estado);
        $this->assertNotNull($documento->cite);
        $this->assertSame('Dra. Elena Vargas', $documento->datos_firmante['nombre']);
        $this->assertSame('Directora Ejecutiva', $documento->datos_firmante['cargo']);
        $this->assertSame('SDAYA S.R.L.', $documento->datos_firmante['empresa']);
        $this->assertSame('elena.vargas@sdaya.com', $documento->datos_firmante['correo']);
        $this->assertSame('+591 75554433', $documento->datos_firmante['telefono']);
    }

    public function test_conservacion_historica_cambio_posterior_de_cargo_no_altera_carta_emitida(): void
    {
        Storage::fake('local');
        config()->set('sdaya.documentos.disk', 'local');

        $firmante = User::factory()->editor()->create([
            'name' => 'Ing. Roberto Gómez',
            'email' => 'roberto.gomez@empresa.com',
            'cargo' => 'Jefe de Operaciones',
            'empresa' => 'SDAYA S.R.L.',
            'telefono' => '+591 73332211',
        ]);
        $area = Area::factory()->create(['codigo' => 'OPS']);
        $tipo = Tipo::factory()->create(['codigo' => 'NE']);

        $documento = Documento::factory()->create([
            'area_id' => $area->id,
            'tipo_id' => $tipo->id,
            'firmante_id' => $firmante->id,
            'datos_firmante' => $firmante->datosPieFirma(),
            'estado' => EstadoDocumento::BORRADOR,
        ]);

        // 1. Se emite la carta con el cargo actual ("Jefe de Operaciones")
        $this->actingAs($firmante)->post(route('documentos.emitir', $documento));

        $documento->refresh();
        $this->assertSame('Jefe de Operaciones', $documento->pieFirma()['cargo']);

        // 2. Meses después, el usuario es ascendido y cambia su perfil
        $firmante->update([
            'name' => 'Ing. Roberto Gómez Morales (MBA)',
            'cargo' => 'Vicepresidente Corporativo',
            'empresa' => 'SDAYA Holdings Corp.',
            'telefono' => '+591 79990000',
        ]);

        // 3. La carta emitida HISTÓRICA conserva exactamente los datos originales
        $documento->refresh();
        $pieHistorico = $documento->pieFirma();

        $this->assertSame('Ing. Roberto Gómez', $pieHistorico['nombre']);
        $this->assertSame('Jefe de Operaciones', $pieHistorico['cargo']);
        $this->assertSame('SDAYA S.R.L.', $pieHistorico['empresa']);
        $this->assertSame('roberto.gomez@empresa.com', $pieHistorico['correo']);
        $this->assertSame('+591 73332211', $pieHistorico['telefono']);

        // 4. La vista show del documento histórico sigue mostrando el cargo previo
        $response = $this->actingAs($firmante)->get(route('documentos.show', $documento));
        $response->assertOk();
        $response->assertSee('Jefe de Operaciones');
        $response->assertDontSee('Vicepresidente Corporativo');
    }

    public function test_alineacion_pie_firma_se_guarda_y_refleja_en_vista_previa(): void
    {
        $firmante = User::factory()->editor()->create([
            'name' => 'Lic. Patricia Soliz',
            'cargo' => 'Jefe de Finanzas',
        ]);
        $area = Area::factory()->create();
        $tipo = Tipo::factory()->create();

        // 1. Guardar con alineación izquierda para el pie de firma y derecha para encabezado
        $response = $this->actingAs($firmante)->post(route('documentos.store'), [
            'area_id' => $area->id,
            'tipo_id' => $tipo->id,
            'fecha_documento' => '2026-09-01',
            'alineacion_encabezado' => 'right',
            'alineacion_pie_firma' => 'left',
            'firmante_id' => $firmante->id,
            'contenido' => '<p>Contenido de prueba de alineación de firma.</p>',
            'accion' => 'guardar',
        ]);

        $documento = Documento::query()->sole();
        $this->assertSame('left', $documento->alineacion_pie_firma);

        $previewResponse = $this->actingAs($firmante)->get(route('documentos.preview', $documento));
        $previewResponse->assertOk();
        $previewResponse->assertSee('text-left');
    }

    public function test_usuario_puede_subir_y_eliminar_imagen_de_firma_en_su_perfil(): void
    {
        Storage::fake('local');
        config()->set('sdaya.documentos.disk', 'local');

        $usuario = User::factory()->editor()->create([
            'name' => 'Lic. Carlos Medina',
        ]);

        $archivoFirma = \Illuminate\Http\UploadedFile::fake()->image('firma.png', 200, 80);

        // 1. Subir imagen de firma
        $response = $this->actingAs($usuario)->put(route('perfil.update'), [
            'name' => 'Lic. Carlos Medina',
            'firma_digital' => $archivoFirma,
        ]);

        $response->assertRedirect(route('perfil.edit'));
        $usuario->refresh();

        $this->assertNotNull($usuario->firma_digital);
        Storage::disk('local')->assertExists($usuario->firma_digital);
        $this->assertNotNull($usuario->firmaDataUri());

        // 2. Eliminar imagen de firma
        $responseDelete = $this->actingAs($usuario)->put(route('perfil.update'), [
            'name' => 'Lic. Carlos Medina',
            'eliminar_firma' => 1,
        ]);

        $responseDelete->assertRedirect(route('perfil.edit'));
        $usuario->refresh();

        $this->assertNull($usuario->firma_digital);
        $this->assertNull($usuario->firmaDataUri());
    }

    public function test_carta_con_firma_digital_muestra_imagen_en_vista_previa_y_emision(): void
    {
        Storage::fake('local');
        config()->set('sdaya.documentos.disk', 'local');

        // Crear archivo de firma en storage fake
        $rutaFirma = 'firmas/firma_test.png';
        Storage::disk('local')->put($rutaFirma, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));

        $firmante = User::factory()->editor()->create([
            'name' => 'Ing. Juan Pérez',
            'cargo' => 'Gerente General',
            'firma_digital' => $rutaFirma,
        ]);
        $area = Area::factory()->create();
        $tipo = Tipo::factory()->create();

        $documento = Documento::factory()->create([
            'area_id' => $area->id,
            'tipo_id' => $tipo->id,
            'firmante_id' => $firmante->id,
            'datos_firmante' => $firmante->datosPieFirma(),
            'estado' => EstadoDocumento::BORRADOR,
        ]);

        // 1. Vista previa muestra la imagen de la firma (data URI)
        $previewResponse = $this->actingAs($firmante)->get(route('documentos.preview', $documento));
        $previewResponse->assertOk();
        $previewResponse->assertSee('data:image/png;base64,');
        $previewResponse->assertSee('Rúbrica de Ing. Juan Pérez');

        // 2. Emisión sella la firma y genera Word y PDF con la imagen
        $this->actingAs($firmante)->post(route('documentos.emitir', $documento));
        $documento->refresh();

        $this->assertSame(EstadoDocumento::EMITIDO, $documento->estado);
        $this->assertSame($rutaFirma, $documento->datos_firmante['firma_digital']);
    }
}
