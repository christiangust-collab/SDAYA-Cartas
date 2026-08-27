<?php

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

Artisan::command('sdaya:estado', function (): void {
    $this->info('La base de SDAYA Cartas está disponible.');
})->purpose('Comprueba que la aplicación puede ejecutar comandos');

Artisan::command('sdaya:usuario {email?}', function (): int {
    $email = mb_strtolower(trim((string) ($this->argument('email') ?: $this->ask('Correo electrónico'))));
    $usuario = User::query()->firstOrNew(['email' => $email]);
    $nombre = trim((string) $this->ask('Nombre completo', $usuario->name ?: null));
    $rolActual = $usuario->role instanceof RolUsuario ? $usuario->role->value : RolUsuario::LECTOR->value;
    $rol = (string) $this->choice(
        'Rol',
        array_map(static fn (RolUsuario $rol): string => $rol->value, RolUsuario::cases()),
        $rolActual,
    );

    $validator = Validator::make(
        ['email' => $email, 'name' => $nombre, 'role' => $rol],
        ['email' => ['required', 'email'], 'name' => ['required', 'string', 'max:255'], 'role' => ['required', 'in:admin,editor,lector']],
    );

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }

        return 1;
    }

    $cambiarClave = ! $usuario->exists || $this->confirm('¿Cambiar también la contraseña?', false);

    if ($cambiarClave) {
        $password = (string) $this->secret('Contraseña nueva (mínimo 12 caracteres)');
        $confirmacion = (string) $this->secret('Repite la contraseña');
        $passwordValidator = Validator::make(
            ['password' => $password, 'password_confirmation' => $confirmacion],
            ['password' => ['required', 'confirmed', Password::min(12)]],
        );

        if ($passwordValidator->fails()) {
            foreach ($passwordValidator->errors()->all() as $error) {
                $this->error($error);
            }

            return 1;
        }

        $usuario->password = $password;
    }

    $usuario->name = $nombre;
    $usuario->email = $email;
    $usuario->role = RolUsuario::from($rol);
    $usuario->save();

    $this->info($usuario->wasRecentlyCreated ? 'Usuario creado correctamente.' : 'Usuario actualizado correctamente.');

    return 0;
})->purpose('Crea o actualiza un usuario de forma interactiva y sin exponer la contraseña');
