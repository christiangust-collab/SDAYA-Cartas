<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Tipo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class GuardarTipoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $tipo = $this->route('tipo');

        return $tipo instanceof Tipo
            ? ($this->user()?->can('update', $tipo) ?? false)
            : ($this->user()?->can('create', Tipo::class) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'codigo' => mb_strtoupper(trim((string) $this->input('codigo'))),
            'nombre' => trim((string) $this->input('nombre')),
            'descripcion' => trim((string) $this->input('descripcion')) ?: null,
        ]);
    }

    public function rules(): array
    {
        $tipo = $this->route('tipo');

        return [
            'codigo' => [
                'required',
                'string',
                'min:2',
                'max:20',
                'regex:/^[A-Z0-9-]+$/',
                Rule::unique('tipos', 'codigo')->ignore($tipo),
            ],
            'nombre' => ['required', 'string', 'min:3', 'max:120'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.regex' => 'El código solo puede contener letras mayúsculas, números y guiones.',
            'codigo.unique' => 'Ya existe un tipo con ese código.',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $tipo = $this->route('tipo');

            if (! $tipo instanceof Tipo || $validator->errors()->has('codigo')) {
                return;
            }

            $codigoCambio = $tipo->codigo !== (string) $this->input('codigo');

            if ($codigoCambio && $tipo->documentos()->whereNotNull('cite')->exists()) {
                $validator->errors()->add(
                    'codigo',
                    'El código no puede cambiar porque ya forma parte de documentos emitidos.',
                );
            }
        }];
    }
}
