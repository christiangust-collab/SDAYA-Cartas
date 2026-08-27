<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tipo;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Tipo> */
final class TipoFactory extends Factory
{
    protected $model = Tipo::class;

    public function definition(): array
    {
        return [
            'codigo' => mb_strtoupper(fake()->unique()->bothify('T##')),
            'nombre' => fake()->unique()->words(2, true),
            'descripcion' => fake()->sentence(),
            'activo' => true,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn (): array => ['activo' => false]);
    }
}
