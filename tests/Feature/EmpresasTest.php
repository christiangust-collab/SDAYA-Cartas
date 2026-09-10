<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\GeneradorArchivosDocumento;
use App\Enums\EstadoDocumento;
use App\Models\Area;
use App\Models\Documento;
use App\Models\Empresa;
use App\Models\Tipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class EmpresasTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrador_puede_listar_crear_editar_y_desactivar_empresas(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();

        // 1. Listar empresas
        $response = $this->actingAs($admin)->get(route('empresas.index'));
        $response->assertOk();
        $response->assertSee('Empresas Registradas');

        // 2. Crear nueva empresa
        $logo = UploadedFile::fake()->image('logo_alfa.png', 200, 100);
        $createResponse = $this->actingAs($admin)->post(route('empresas.store'), [
            'nombre' => 'Alfa Corp S.R.L.',
            'nit' => '987654321',
            'direccion' => 'Av. Arce #100, La Paz',
            'telefono' => '+591 2 2444555',
            'correo' => 'contacto@alfacorp.bo',
            'sitio_web' => 'https://alfacorp.bo',
            'logo' => $logo,
            'activo' => '1',
        ]);

        $createResponse->assertRedirect(route('empresas.index'));
        $createResponse->assertSessionHas('success');

        $empresa = Empresa::query()->where('nombre', 'Alfa Corp S.R.L.')->first();
        $this->assertNotNull($empresa);
        $this->assertSame('987654321', $empresa->nit);
        $this->assertNotNull($empresa->logo);
        $this->assertTrue($empresa->activo);
        Storage::disk('local')->assertExists($empresa->logo);

        // 3. Editar empresa
        $editResponse = $this->actingAs($admin)->put(route('empresas.update', $empresa), [
            'nombre' => 'Alfa Corporation Internacional S.R.L.',
            'nit' => '987654321',
            'direccion' => 'Av. Arce #100, Piso 5, La Paz',
            'telefono' => '+591 2 2444555',
            'correo' => 'info@alfacorp.bo',
            'sitio_web' => 'https://alfacorp.bo',
            'activo' => '1',
        ]);

        $editResponse->assertRedirect(route('empresas.index'));
        $empresa->refresh();
        $this->assertSame('Alfa Corporation Internacional S.R.L.', $empresa->nombre);
        $this->assertSame('info@alfacorp.bo', $empresa->correo);

        // 4. Toggle desactivar y reactivar empresa
        $toggleResponse = $this->actingAs($admin)->patch(route('empresas.toggle', $empresa));
        $toggleResponse->assertRedirect();
        $empresa->refresh();
        $this->assertFalse($empresa->activo);

        $toggleResponse2 = $this->actingAs($admin)->patch(route('empresas.toggle', $empresa));
        $toggleResponse2->assertRedirect();
        $empresa->refresh();
        $this->assertTrue($empresa->activo);
    }

    public function test_usuario_no_administrador_no_puede_administrar_empresas(): void
    {
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor)->get(route('empresas.index'))->assertForbidden();
        $this->actingAs($editor)->get(route('empresas.create'))->assertForbidden();
        $this->actingAs($editor)->post(route('empresas.store'), ['nombre' => 'Test'])->assertForbidden();

        $empresa = Empresa::query()->first();
        if ($empresa) {
            $this->actingAs($editor)->get(route('empresas.edit', $empresa))->assertForbidden();
            $this->actingAs($editor)->put(route('empresas.update', $empresa), ['nombre' => 'Test'])->assertForbidden();
            $this->actingAs($editor)->patch(route('empresas.toggle', $empresa))->assertForbidden();
        }
    }

    public function test_administrador_puede_asignar_firmante_a_empresa(): void
    {
        $admin = User::factory()->admin()->create();
        $empresa = Empresa::query()->create([
            'nombre' => 'Tecnología Andina S.A.',
            'nit' => '554433221',
            'activo' => true,
        ]);
        $firmante = User::factory()->editor()->create([
            'name' => 'Ing. Marcelo Quiroga',
            'cargo' => 'Jefe de Sistemas',
        ]);

        $response = $this->actingAs($admin)->post(route('empresas.asignar-firmante'), [
            'user_id' => $firmante->id,
            'empresa_id' => $empresa->id,
            'cargo' => 'Director de Tecnología',
            'telefono' => '+591 76543210',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $firmante->refresh();
        $this->assertSame($empresa->id, $firmante->empresa_id);
        $this->assertSame('Director de Tecnología', $firmante->cargo);
        $this->assertSame('+591 76543210', $firmante->telefono);
    }

    public function test_dos_empresas_diferentes_generan_cartas_independientes_con_sus_propios_datos_institucionales(): void
    {
        // Empresa A: Alfa Corp S.R.L.
        $empresaA = Empresa::query()->create([
            'nombre' => 'Alfa Corp S.R.L.',
            'nit' => '1111222233',
            'direccion' => 'Calle 1 #100, La Paz',
            'telefono' => '+591 2 2111111',
            'correo' => 'contacto@alfacorp.bo',
            'sitio_web' => 'https://alfacorp.bo',
            'activo' => true,
        ]);
        $firmanteA = User::factory()->editor()->create([
            'name' => 'Lic. Juan Pérez',
            'cargo' => 'Gerente General',
            'empresa_id' => $empresaA->id,
            'email' => 'juan@alfacorp.bo',
            'telefono' => '+591 71111111',
        ]);

        // Empresa B: Beta Labs S.A.
        $empresaB = Empresa::query()->create([
            'nombre' => 'Beta Labs S.A.',
            'nit' => '9999888877',
            'direccion' => 'Av. Ballivián #500, Cochabamba',
            'telefono' => '+591 4 4222222',
            'correo' => 'info@betalabs.bo',
            'sitio_web' => 'https://betalabs.bo',
            'activo' => true,
        ]);
        $firmanteB = User::factory()->editor()->create([
            'name' => 'Dra. María Gómez',
            'cargo' => 'Directora de Operaciones',
            'empresa_id' => $empresaB->id,
            'email' => 'maria@betalabs.bo',
            'telefono' => '+591 72222222',
        ]);

        $area = Area::factory()->create(['codigo' => 'GER']);
        $tipo = Tipo::factory()->create(['codigo' => 'NOT']);

        // 1. Firmante A crea una carta para Empresa A
        $this->actingAs($firmanteA)->post(route('documentos.store'), [
            'area_id' => $area->id,
            'tipo_id' => $tipo->id,
            'fecha_documento' => '2026-09-03',
            'asunto' => 'Propuesta Comercial Alfa',
            'destinatario' => 'Cliente Uno',
            'firmante_id' => $firmanteA->id,
            'contenido' => '<p>Contenido oficial de Alfa Corp.</p>',
            'alineacion_encabezado' => 'right',
            'alineacion_pie_firma' => 'right',
            'accion' => 'guardar',
        ]);

        $documentoA = Documento::query()->where('asunto', 'Propuesta Comercial Alfa')->firstOrFail();
        $this->assertSame($empresaA->id, $documentoA->empresa_id);
        $this->assertSame('Alfa Corp S.R.L.', $documentoA->datosEmpresa()['nombre']);
        $this->assertSame('Alfa Corp S.R.L.', $documentoA->pieFirma()['empresa']);
        $this->assertSame('Lic. Juan Pérez', $documentoA->pieFirma()['nombre']);
        $this->assertSame('Gerente General', $documentoA->pieFirma()['cargo']);

        // 2. Firmante B crea una carta para Empresa B
        $this->actingAs($firmanteB)->post(route('documentos.store'), [
            'area_id' => $area->id,
            'tipo_id' => $tipo->id,
            'fecha_documento' => '2026-09-03',
            'asunto' => 'Informe Técnico Beta',
            'destinatario' => 'Cliente Dos',
            'firmante_id' => $firmanteB->id,
            'contenido' => '<p>Contenido oficial de Beta Labs.</p>',
            'alineacion_encabezado' => 'left',
            'alineacion_pie_firma' => 'left',
            'accion' => 'guardar',
        ]);

        $documentoB = Documento::query()->where('asunto', 'Informe Técnico Beta')->firstOrFail();
        $this->assertSame($empresaB->id, $documentoB->empresa_id);
        $this->assertSame('Beta Labs S.A.', $documentoB->datosEmpresa()['nombre']);
        $this->assertSame('Beta Labs S.A.', $documentoB->pieFirma()['empresa']);
        $this->assertSame('Dra. María Gómez', $documentoB->pieFirma()['nombre']);
        $this->assertSame('Directora de Operaciones', $documentoB->pieFirma()['cargo']);

        // 3. Verificar Vista Previa de ambas cartas
        $previewA = $this->actingAs($firmanteA)->get(route('documentos.preview', $documentoA));
        $previewA->assertOk();
        $previewA->assertSee('Alfa Corp S.R.L.');
        $previewA->assertSee('Lic. Juan Pérez');
        $previewA->assertSee('Gerente General');

        $previewB = $this->actingAs($firmanteB)->get(route('documentos.preview', $documentoB));
        $previewB->assertOk();
        $previewB->assertSee('Beta Labs S.A.');
        $previewB->assertSee('Dra. María Gómez');
        $previewB->assertSee('Directora de Operaciones');
    }

    public function test_conservacion_historica_modificacion_posterior_de_la_empresa_no_altera_la_carta_emitida(): void
    {
        Storage::fake('local');

        $empresa = Empresa::query()->create([
            'nombre' => 'Original Consulting S.R.L.',
            'nit' => '1234567',
            'direccion' => 'Dirección Antigua #10',
            'telefono' => '+591 2 2000000',
            'correo' => 'antiguo@consulting.bo',
            'sitio_web' => 'https://antiguo.bo',
            'activo' => true,
        ]);

        $firmante = User::factory()->editor()->create([
            'name' => 'Lic. Roberto Paz',
            'cargo' => 'Jefe de Proyectos',
            'empresa_id' => $empresa->id,
            'email' => 'roberto@consulting.bo',
            'telefono' => '+591 70011223',
        ]);

        $area = Area::factory()->create(['codigo' => 'PRO']);
        $tipo = Tipo::factory()->create(['codigo' => 'INF']);

        // 1. Crear y emitir la carta oficialmente
        $this->actingAs($firmante)->post(route('documentos.store'), [
            'area_id' => $area->id,
            'tipo_id' => $tipo->id,
            'fecha_documento' => '2026-09-03',
            'asunto' => 'Carta Oficial Inmutable',
            'destinatario' => 'Ministerio de Planificación',
            'firmante_id' => $firmante->id,
            'contenido' => '<p>Informe de auditoría emitido con datos originales.</p>',
            'alineacion_encabezado' => 'right',
            'alineacion_pie_firma' => 'right',
            'accion' => 'emitir',
        ]);

        $documentoEmitido = Documento::query()->where('asunto', 'Carta Oficial Inmutable')->sole();
        $this->assertSame(EstadoDocumento::EMITIDO, $documentoEmitido->estado);
        $this->assertNotNull($documentoEmitido->datos_empresa);
        $this->assertSame('Original Consulting S.R.L.', $documentoEmitido->datos_empresa['nombre']);
        $this->assertSame('1234567', $documentoEmitido->datos_empresa['nit']);
        $this->assertSame('Dirección Antigua #10', $documentoEmitido->datos_empresa['direccion']);

        // 2. La empresa posteriormente cambia de nombre, NIT, dirección y correo
        $empresa->update([
            'nombre' => 'Global Solutions International S.A.',
            'nit' => '9999999',
            'direccion' => 'Nueva Sede Torre Financiera Piso 20',
            'telefono' => '+591 2 2999999',
            'correo' => 'nuevo@globalsolutions.bo',
        ]);

        // Y el firmante cambia de cargo y correo
        $firmante->update([
            'cargo' => 'Vicepresidente Regional',
            'email' => 'roberto.paz@globalsolutions.bo',
        ]);

        // 3. Verificar que la carta emitida históricamente CONSERVA intactos los datos originales
        $documentoEmitido->refresh();
        $this->assertSame('Original Consulting S.R.L.', $documentoEmitido->datosEmpresa()['nombre']);
        $this->assertSame('1234567', $documentoEmitido->datosEmpresa()['nit']);
        $this->assertSame('Dirección Antigua #10', $documentoEmitido->datosEmpresa()['direccion']);

        $pieHistorico = $documentoEmitido->pieFirma();
        $this->assertSame('Original Consulting S.R.L.', $pieHistorico['empresa']);
        $this->assertSame('Jefe de Proyectos', $pieHistorico['cargo']);
        $this->assertSame('roberto@consulting.bo', $pieHistorico['correo']);

        // 4. La vista show/preview web sigue mostrando los datos históricos originales
        $responseShow = $this->actingAs($firmante)->get(route('documentos.show', $documentoEmitido));
        $responseShow->assertOk();
        $responseShow->assertSee('Original Consulting S.R.L.');
        $responseShow->assertSee('Jefe de Proyectos');
        $responseShow->assertDontSee('Global Solutions International S.A.');
    }

    public function test_generador_archivos_utiliza_datos_de_la_empresa_asociada(): void
    {
        Storage::fake('local');

        $empresa = Empresa::query()->create([
            'nombre' => 'Industrias del Valle S.A.',
            'nit' => '33445566',
            'direccion' => 'Parque Industrial Lote 12, Cochabamba',
            'telefono' => '+591 4 4112233',
            'correo' => 'ventas@industriasdelvalle.bo',
            'sitio_web' => 'https://industriasdelvalle.bo',
            'activo' => true,
        ]);

        $firmante = User::factory()->editor()->create([
            'name' => 'Ing. Fernando Roca',
            'cargo' => 'Gerente de Producción',
            'empresa_id' => $empresa->id,
            'telefono' => '+591 73334455',
        ]);

        $area = Area::factory()->create(['codigo' => 'PROD']);
        $tipo = Tipo::factory()->create(['codigo' => 'INF']);

        $documento = Documento::factory()->create([
            'area_id' => $area->id,
            'tipo_id' => $tipo->id,
            'empresa_id' => $empresa->id,
            'firmante_id' => $firmante->id,
            'estado' => EstadoDocumento::BORRADOR,
            'hash_verificacion' => '11223344556677889900aabbccddeeff11223344556677889900aabbccddeeff',
            'asunto' => 'Reporte de Eficiencia de Planta',
            'destinatario' => 'Directorio',
            'contenido' => '<p>Reporte correspondiente al primer semestre.</p>',
            'alineacion_encabezado' => 'right',
            'alineacion_pie_firma' => 'right',
        ]);

        /** @var GeneradorArchivosDocumento $generador */
        $generador = app(GeneradorArchivosDocumento::class);
        $archivos = $generador->generar($documento);

        $this->assertNotEmpty($archivos->pdf);
        $this->assertNotEmpty($archivos->docx);
        Storage::disk('local')->assertExists($archivos->pdf);
        Storage::disk('local')->assertExists($archivos->docx);
    }
}