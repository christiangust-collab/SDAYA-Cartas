<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Area;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Area> */
final class AreaFactory extends Factory
{
    protected $model = Area::class;

    public function definition(): array
    {
        return [
            'codigo' => mb_strtoupper(fake()->unique()->bothify('A##')),
            'nombre' => fake()->unique()->words(3, true),
            'descripcion' => fake()->sentence(),
            'activo' => true,
        ];
    }

    public function inactiva(): static
    {
        return $this->state(fn (): array => ['activo' => false]);
    }
}
