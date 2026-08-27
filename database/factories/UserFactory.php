<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RolUsuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => RolUsuario::LECTOR,
            'remember_token' => Str::random(10),
        ];
    }

    public function administrador(): static
    {
        return $this->state(fn (): array => ['role' => RolUsuario::ADMIN]);
    }

    public function editor(): static
    {
        return $this->state(fn (): array => ['role' => RolUsuario::EDITOR]);
    }

    public function lector(): static
    {
        return $this->state(fn (): array => ['role' => RolUsuario::LECTOR]);
    }
}
