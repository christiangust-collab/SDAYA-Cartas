<?php

namespace Database\Seeders;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsuarioAdministradorSeeder extends Seeder
{
    public function run(): void
    {
        $email = Str::lower(trim((string) ($_ENV['ADMIN_EMAIL'] ?? $_SERVER['ADMIN_EMAIL'] ?? getenv('ADMIN_EMAIL') ?? env('ADMIN_EMAIL', 'admin@sdaya.local'))));
        $password = (string) ($_ENV['ADMIN_PASSWORD'] ?? $_SERVER['ADMIN_PASSWORD'] ?? getenv('ADMIN_PASSWORD') ?? env('ADMIN_PASSWORD', '8uVHbb49j18B'));
        $nombre = trim((string) ($_ENV['ADMIN_NAME'] ?? $_SERVER['ADMIN_NAME'] ?? getenv('ADMIN_NAME') ?? env('ADMIN_NAME', 'Administrador SDAYA')));
        if ($nombre === '') {
            $nombre = 'Administrador SDAYA';
        }

        if ($email === '' || $password === '') {
            $this->command?->warn('ADMIN_EMAIL o ADMIN_PASSWORD no están definidos. Se omite la configuración del administrador inicial.');

            return;
        }

        $empresa = \App\Models\Empresa::query()->where('es_predeterminada', true)->first() ?? \App\Models\Empresa::query()->first();

        $usuario = User::query()->firstOrNew(['email' => $email]);
        $usuario->name = $nombre;
        $usuario->password = $password;
        $usuario->role = RolUsuario::ADMIN;
        $usuario->activo = true;
        $usuario->cargo = $usuario->cargo ?? 'Director General de Sistemas';
        $usuario->empresa = $usuario->empresa ?? ($empresa?->nombre ?? 'SDAYA S.R.L.');
        $usuario->empresa_id = $usuario->empresa_id ?? $empresa?->id;
        $usuario->telefono = $usuario->telefono ?? '+591 70000000';
        $usuario->save();
    }
}
