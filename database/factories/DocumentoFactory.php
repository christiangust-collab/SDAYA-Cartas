<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EstadoDocumento;
use App\Models\Area;
use App\Models\Documento;
use App\Models\Tipo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Documento> */
final class DocumentoFactory extends Factory
{
    protected $model = Documento::class;

    public function definition(): array
    {
        $fecha = fake()->dateTimeBetween('-6 months', '+1 month');

        return [
            'area_id' => Area::factory(),
            'tipo_id' => Tipo::factory(),
            'anio' => (int) $fecha->format('Y'),
            'fecha_documento' => $fecha,
            'asunto' => fake()->sentence(6),
            'destinatario' => fake()->name(),
            'contenido' => '<p>'.e(fake()->paragraph()).'</p>',
            'estado' => EstadoDocumento::BORRADOR,
        ];
    }

    public function emitido(int $correlativo = 1): static
    {
        return $this->state(function (array $attributes) use ($correlativo): array {
            $anio = (int) ($attributes['anio'] ?? now()->year);

            return [
                'cite' => sprintf('SD-ADM-NE-%d/%03d', $anio, $correlativo),
                'correlativo' => $correlativo,
                'estado' => EstadoDocumento::EMITIDO,
                'hash_verificacion' => bin2hex(random_bytes(32)),
                'hash_contenido' => hash('sha256', Str::random(30)),
                'hash_archivo_pdf' => hash('sha256', Str::random(30)),
                'archivo_docx' => 'documentos/prueba/documento.docx',
                'archivo_pdf' => 'documentos/prueba/documento.pdf',
                'emitido_por' => User::factory()->editor(),
                'emitido_at' => now(),
            ];
        });
    }

    public function anulado(): static
    {
        return $this->emitido()->state(fn (): array => ['estado' => EstadoDocumento::ANULADO]);
    }
}
