<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\EstadoDocumento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class FiltrarDocumentosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['buscar' => trim((string) $this->input('buscar')) ?: null]);
    }

    public function rules(): array
    {
        return [
            'buscar' => ['nullable', 'string', 'max:100'],
            'empresa_id' => ['nullable', 'integer', 'exists:empresas,id'],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'tipo_id' => ['nullable', 'integer', 'exists:tipos,id'],
            'anio' => ['nullable', 'integer', 'between:2020,2100'],
            'estado' => ['nullable', Rule::enum(EstadoDocumento::class)],
        ];
    }
}
