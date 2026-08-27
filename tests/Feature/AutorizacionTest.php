<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Documento;
use App\Models\Tipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AutorizacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_invitado_es_redirigido_al_login_desde_el_panel(): void
    {
        $this->get(route('documentos.index'))->assertRedirect(route('login'));
    }

    public function test_un_lector_puede_consultar_pero_no_crear_documentos(): void
    {
        $lector = User::factory()->lector()->create();
        $documento = Documento::factory()->create();

        $this->actingAs($lector)->get(route('documentos.index'))->assertOk();
        $this->actingAs($lector)->get(route('documentos.show', $documento))->assertOk();
        $this->actingAs($lector)->get(route('documentos.create'))->assertForbidden();
    }

    public function test_solo_un_administrador_accede_a_catalogos(): void
    {
        $admin = User::factory()->administrador()->create();
        $editor = User::factory()->editor()->create();

        $this->actingAs($admin)->get(route('catalogos.index'))->assertOk();
        $this->actingAs($editor)->get(route('catalogos.index'))->assertForbidden();
    }

    public function test_un_administrador_desactiva_catalogos_sin_eliminarlos(): void
    {
        $admin = User::factory()->administrador()->create();
        $area = Area::factory()->create();
        $tipo = Tipo::factory()->create();

        $this->actingAs($admin)->patch(route('catalogos.areas.toggle', $area))->assertRedirect();
        $this->actingAs($admin)->patch(route('catalogos.tipos.toggle', $tipo))->assertRedirect();

        $this->assertDatabaseHas('areas', ['id' => $area->id, 'activo' => false]);
        $this->assertDatabaseHas('tipos', ['id' => $tipo->id, 'activo' => false]);
    }

    public function test_no_se_cambia_un_codigo_que_ya_forma_parte_de_un_cite(): void
    {
        $admin = User::factory()->administrador()->create();
        $area = Area::factory()->create(['codigo' => 'ADM']);
        $tipo = Tipo::factory()->create(['codigo' => 'NE']);
        Documento::factory()->emitido()->create([
            'area_id' => $area->id,
            'tipo_id' => $tipo->id,
            'anio' => 2026,
            'cite' => 'SD-ADM-NE-2026/001',
        ]);

        $this->actingAs($admin)->put(route('catalogos.areas.update', $area), [
            'codigo' => 'LEG',
            'nombre' => $area->nombre,
            'descripcion' => $area->descripcion,
        ])->assertSessionHasErrors('codigo');

        $this->assertSame('ADM', $area->fresh()->codigo);
    }

    public function test_el_menu_muestra_apartados_de_administracion_solo_al_administrador(): void
    {
        $admin = User::factory()->administrador()->create();
        $editor = User::factory()->editor()->create();
        $lector = User::factory()->lector()->create();

        // El administrador ve el enlace a Catálogos en el menú y dentro ve Tipos de Área y Tipos de Documento
        $respAdmin = $this->actingAs($admin)->get(route('documentos.index'));
        $respAdmin->assertOk();
        $respAdmin->assertSee('Catálogos');

        $respCatalogos = $this->actingAs($admin)->get(route('catalogos.index'));
        $respCatalogos->assertOk();
        $respCatalogos->assertSee('Tipos de Área');
        $respCatalogos->assertSee('Tipos de Documento');

        // El editor puede crear documentos pero no ve la sección de administración de catálogos
        $respEditor = $this->actingAs($editor)->get(route('documentos.index'));
        $respEditor->assertOk();
        $respEditor->assertSee('Nuevo documento');
        $respEditor->assertDontSee('Catálogos');

        // El lector no ve catálogos ni creación de documentos
        $respLector = $this->actingAs($lector)->get(route('documentos.index'));
        $respLector->assertOk();
        $respLector->assertDontSee('Catálogos');
        $respLector->assertDontSee('Nuevo documento');
    }

    public function test_administrador_ejecuta_crud_completo_de_areas_y_tipos(): void
    {
        $admin = User::factory()->administrador()->create();

        // 1. Crear nueva área
        $respCrearArea = $this->actingAs($admin)->post(route('catalogos.areas.store'), [
            'codigo' => 'RH',
            'nombre' => 'Recursos Humanos',
            'descripcion' => 'Gestión de personal',
        ]);
        $respCrearArea->assertRedirect();
        $area = Area::query()->where('codigo', 'RH')->firstOrFail();
        $this->assertSame('Recursos Humanos', $area->nombre);

        // 2. Editar área
        $respEditarArea = $this->actingAs($admin)->put(route('catalogos.areas.update', $area), [
            'codigo' => 'RRHH',
            'nombre' => 'Talento Humano',
            'descripcion' => 'Actualizado',
        ]);
        $respEditarArea->assertRedirect();
        $this->assertSame('RRHH', $area->fresh()->codigo);
        $this->assertSame('Talento Humano', $area->fresh()->nombre);

        // 3. Crear nuevo tipo
        $respCrearTipo = $this->actingAs($admin)->post(route('catalogos.tipos.store'), [
            'codigo' => 'INF',
            'nombre' => 'Informe Técnico',
            'descripcion' => 'Documentos de reporte',
        ]);
        $respCrearTipo->assertRedirect();
        $tipo = Tipo::query()->where('codigo', 'INF')->firstOrFail();
        $this->assertSame('Informe Técnico', $tipo->nombre);

        // 4. Editar tipo
        $respEditarTipo = $this->actingAs($admin)->put(route('catalogos.tipos.update', $tipo), [
            'codigo' => 'IT',
            'nombre' => 'Informe Técnico General',
            'descripcion' => 'Actualizado',
        ]);
        $respEditarTipo->assertRedirect();
        $this->assertSame('IT', $tipo->fresh()->codigo);
        $this->assertSame('Informe Técnico General', $tipo->fresh()->nombre);
    }

    public function test_seeder_crea_administrador_y_permite_login(): void
    {
        $_ENV['ADMIN_EMAIL'] = $_SERVER['ADMIN_EMAIL'] = 'admin_test@sdaya.local';
        $_ENV['ADMIN_PASSWORD'] = $_SERVER['ADMIN_PASSWORD'] = 'ClaveSegura123!';
        $_ENV['ADMIN_NAME'] = $_SERVER['ADMIN_NAME'] = 'Admin SDAYA Test';

        $this->seed(\Database\Seeders\UsuarioAdministradorSeeder::class);

        $this->assertDatabaseHas('users', [
            'email' => 'admin_test@sdaya.local',
            'name' => 'Admin SDAYA Test',
            'role' => \App\Enums\RolUsuario::ADMIN->value,
        ]);

        $response = $this->post(route('login.store'), [
            'email' => 'admin_test@sdaya.local',
            'password' => 'ClaveSegura123!',
        ]);

        $response->assertRedirect(route('documentos.index', absolute: false));
        $this->assertAuthenticated();
    }

    public function test_seeder_actualiza_contrasena_de_administrador_existente_sin_duplicar(): void
    {
        $_ENV['ADMIN_EMAIL'] = $_SERVER['ADMIN_EMAIL'] = 'admin_test@sdaya.local';
        $_ENV['ADMIN_PASSWORD'] = $_SERVER['ADMIN_PASSWORD'] = 'ClaveInicial123!';
        $_ENV['ADMIN_NAME'] = $_SERVER['ADMIN_NAME'] = 'Admin SDAYA';
        $this->seed(\Database\Seeders\UsuarioAdministradorSeeder::class);

        $this->assertEquals(1, User::query()->where('email', 'admin_test@sdaya.local')->count());

        // Simular cambio de ADMIN_PASSWORD en entorno (ej. Render)
        $_ENV['ADMIN_PASSWORD'] = $_SERVER['ADMIN_PASSWORD'] = 'NuevaClaveSegura456!';
        $this->seed(\Database\Seeders\UsuarioAdministradorSeeder::class);

        // Debe seguir habiendo 1 solo usuario
        $this->assertEquals(1, User::query()->where('email', 'admin_test@sdaya.local')->count());

        // Clave anterior debe fallar
        $failResponse = $this->post(route('login.store'), [
            'email' => 'admin_test@sdaya.local',
            'password' => 'ClaveInicial123!',
        ]);
        $failResponse->assertSessionHasErrors('email');
        $this->assertGuest();

        // Nueva clave debe autenticar exitosamente
        $successResponse = $this->post(route('login.store'), [
            'email' => 'admin_test@sdaya.local',
            'password' => 'NuevaClaveSegura456!',
        ]);
        $successResponse->assertRedirect(route('documentos.index', absolute: false));
        $this->assertAuthenticated();
    }

    public function test_login_con_credenciales_invalidas_muestra_error(): void
    {
        $_ENV['ADMIN_EMAIL'] = $_SERVER['ADMIN_EMAIL'] = 'admin_test@sdaya.local';
        $_ENV['ADMIN_PASSWORD'] = $_SERVER['ADMIN_PASSWORD'] = 'ClaveCorrecta123!';
        $this->seed(\Database\Seeders\UsuarioAdministradorSeeder::class);

        $response = $this->post(route('login.store'), [
            'email' => 'admin_test@sdaya.local',
            'password' => 'ClaveIncorrecta999!',
        ]);

        $response->assertSessionHasErrors(['email' => 'Las credenciales ingresadas no son correctas.']);
        $this->assertGuest();
    }

    public function test_login_normaliza_mayusculas_y_espacios(): void
    {
        $_ENV['ADMIN_EMAIL'] = $_SERVER['ADMIN_EMAIL'] = 'admin_test@sdaya.local';
        $_ENV['ADMIN_PASSWORD'] = $_SERVER['ADMIN_PASSWORD'] = 'ClaveCorrecta123!';
        $this->seed(\Database\Seeders\UsuarioAdministradorSeeder::class);

        $response = $this->post(route('login.store'), [
            'email' => '  ADMIN_TEST@sdaya.LOCAL  ',
            'password' => 'ClaveCorrecta123!',
        ]);

        $response->assertRedirect(route('documentos.index', absolute: false));
        $this->assertAuthenticated();
    }
}
