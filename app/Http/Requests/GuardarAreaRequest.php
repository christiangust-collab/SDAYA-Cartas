<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Area;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class GuardarAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $area = $this->route('area');

        return $area instanceof Area
            ? ($this->user()?->can('update', $area) ?? false)
            : ($this->user()?->can('create', Area::class) ?? false);
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
        $area = $this->route('area');

        return [
            'codigo' => [
                'required',
                'string',
                'min:2',
                'max:20',
                'regex:/^[A-Z0-9-]+$/',
                Rule::unique('areas', 'codigo')->ignore($area),
            ],
            'nombre' => ['required', 'string', 'min:3', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.regex' => 'El código solo puede contener letras mayúsculas, números y guiones.',
            'codigo.unique' => 'Ya existe un área con ese código.',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $area = $this->route('area');

            if (! $area instanceof Area || $validator->errors()->has('codigo')) {
                return;
            }

            $codigoCambio = $area->codigo !== (string) $this->input('codigo');

            if ($codigoCambio && $area->documentos()->whereNotNull('cite')->exists()) {
                $validator->errors()->add(
                    'codigo',
                    'El código no puede cambiar porque ya forma parte de documentos emitidos.',
                );
            }
        }];
    }
}
