<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class UsuarioControllerTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->empresa = Empresa::query()->create([
            'nombre' => 'SDAYA S.R.L.',
            'nit' => '1028374029',
            'activo' => true,
        ]);
    }

    public function test_administrador_puede_ver_listado_de_usuarios(): void
    {
        $admin = User::factory()->admin()->create(['empresa_id' => $this->empresa->id]);
        $editor = User::factory()->editor()->create(['empresa_id' => $this->empresa->id, 'name' => 'Carlos Editor']);

        $response = $this->actingAs($admin)->get(route('usuarios.index'));

        $response->assertOk();
        $response->assertSee('Usuarios y Cuentas');
        $response->assertSee('Carlos Editor');
        $response->assertSee('Nuevo Usuario');
    }

    public function test_editor_no_puede_acceder_a_usuarios(): void
    {
        $editor = User::factory()->editor()->create(['empresa_id' => $this->empresa->id]);

        $this->actingAs($editor)->get(route('usuarios.index'))->assertForbidden();
        $this->actingAs($editor)->get(route('usuarios.create'))->assertForbidden();
        $this->actingAs($editor)->post(route('usuarios.store'), [])->assertForbidden();
    }

    public function test_administrador_puede_crear_un_nuevo_usuario(): void
    {
        $admin = User::factory()->admin()->create(['empresa_id' => $this->empresa->id]);

        $response = $this->actingAs($admin)->post(route('usuarios.store'), [
            'name' => 'Lic. María Flores',
            'email' => 'mflores@sdaya.bo',
            'password' => 'Secreto123*',
            'password_confirmation' => 'Secreto123*',
            'role' => RolUsuario::EDITOR->value,
            'empresa_id' => $this->empresa->id,
            'cargo' => 'Secretaria General',
            'telefono' => '+591 71234567',
            'activo' => '1',
        ]);

        $response->assertRedirect(route('usuarios.index'));
        $response->assertSessionHas('success');

        $nuevo = User::query()->where('email', 'mflores@sdaya.bo')->first();
        $this->assertNotNull($nuevo);
        $this->assertSame('Lic. María Flores', $nuevo->name);
        $this->assertSame(RolUsuario::EDITOR, $nuevo->role);
        $this->assertSame($this->empresa->id, $nuevo->empresa_id);
        $this->assertSame('Secretaria General', $nuevo->cargo);
        $this->assertTrue($nuevo->estaActivo());
        $this->assertTrue(Hash::check('Secreto123*', $nuevo->password));
    }

    public function test_validacion_falla_con_correo_duplicado_o_password_invalida(): void
    {
        $admin = User::factory()->admin()->create(['empresa_id' => $this->empresa->id]);
        User::factory()->create(['email' => 'existente@sdaya.bo', 'empresa_id' => $this->empresa->id]);

        $response = $this->actingAs($admin)->post(route('usuarios.store'), [
            'name' => 'Usuario Duplicado',
            'email' => 'existente@sdaya.bo',
            'password' => 'corta',
            'password_confirmation' => 'diferente',
            'role' => RolUsuario::EDITOR->value,
            'empresa_id' => $this->empresa->id,
        ]);

        $response->assertSessionHasErrors(['email', 'password']);
    }

    public function test_administrador_puede_editar_usuario_sin_cambiar_password(): void
    {
        $admin = User::factory()->admin()->create(['empresa_id' => $this->empresa->id]);
        $usuario = User::factory()->editor()->create([
            'empresa_id' => $this->empresa->id,
            'name' => 'Juan Original',
            'password' => Hash::make('PasswordAnterior123'),
        ]);

        $oldPasswordHash = $usuario->password;

        $response = $this->actingAs($admin)->put(route('usuarios.update', $usuario), [
            'name' => 'Juan Modificado',
            'email' => $usuario->email,
            'role' => RolUsuario::ADMIN->value,
            'empresa_id' => $this->empresa->id,
            'cargo' => 'Jefe de Sistemas',
            'password' => '',
            'password_confirmation' => '',
            'activo' => '1',
        ]);

        $response->assertRedirect(route('usuarios.index'));
        $usuario->refresh();

        $this->assertSame('Juan Modificado', $usuario->name);
        $this->assertSame(RolUsuario::ADMIN, $usuario->role);
        $this->assertSame('Jefe de Sistemas', $usuario->cargo);
        $this->assertSame($oldPasswordHash, $usuario->password);
    }

    public function test_administrador_puede_cambiar_password_de_usuario(): void
    {
        $admin = User::factory()->admin()->create(['empresa_id' => $this->empresa->id]);
        $usuario = User::factory()->editor()->create([
            'empresa_id' => $this->empresa->id,
            'password' => Hash::make('PasswordAntigua99'),
        ]);

        $response = $this->actingAs($admin)->put(route('usuarios.update', $usuario), [
            'name' => $usuario->name,
            'email' => $usuario->email,
            'role' => $usuario->role->value,
            'empresa_id' => $this->empresa->id,
            'password' => 'NuevaPassword123*',
            'password_confirmation' => 'NuevaPassword123*',
            'activo' => '1',
        ]);

        $response->assertRedirect(route('usuarios.index'));
        $usuario->refresh();

        $this->assertTrue(Hash::check('NuevaPassword123*', $usuario->password));
    }

    public function test_administrador_puede_alternar_estado_activo_de_otro_usuario(): void
    {
        $admin = User::factory()->admin()->create(['empresa_id' => $this->empresa->id]);
        $usuario = User::factory()->editor()->create(['empresa_id' => $this->empresa->id, 'activo' => true]);

        // Desactivar
        $response = $this->actingAs($admin)->patch(route('usuarios.toggle', $usuario));
        $response->assertRedirect();
        $this->assertFalse($usuario->fresh()->estaActivo());

        // Reactivar
        $response = $this->actingAs($admin)->patch(route('usuarios.toggle', $usuario));
        $response->assertRedirect();
        $this->assertTrue($usuario->fresh()->estaActivo());
    }

    public function test_administrador_no_puede_desactivar_su_propia_cuenta(): void
    {
        $admin = User::factory()->admin()->create(['empresa_id' => $this->empresa->id, 'activo' => true]);

        $response = $this->actingAs($admin)->patch(route('usuarios.toggle', $admin));
        $response->assertForbidden();

        $this->assertTrue($admin->fresh()->estaActivo());
    }

    public function test_usuario_activo_puede_iniciar_sesion_e_inactivo_es_rechazado(): void
    {
        $usuario = User::factory()->editor()->create([
            'empresa_id' => $this->empresa->id,
            'email' => 'operador@sdaya.bo',
            'password' => Hash::make('ClaveSegura123'),
            'activo' => true,
        ]);

        // 1. Iniciar sesión activo
        $response = $this->post(route('login.store'), [
            'email' => 'operador@sdaya.bo',
            'password' => 'ClaveSegura123',
        ]);
        $response->assertRedirect(route('documentos.index', absolute: false));
        $this->assertAuthenticatedAs($usuario);

        // Logout
        $this->post(route('logout'));
        $this->assertGuest();

        // 2. Desactivar usuario
        $usuario->update(['activo' => false]);

        // Intentar iniciar sesión inactivo
        $failResponse = $this->post(route('login.store'), [
            'email' => 'operador@sdaya.bo',
            'password' => 'ClaveSegura123',
        ]);
        $failResponse->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
