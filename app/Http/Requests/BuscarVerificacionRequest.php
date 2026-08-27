<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class BuscarVerificacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['codigo' => mb_strtolower(trim((string) $this->input('codigo')))]);
    }

    public function rules(): array
    {
        return ['codigo' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/']];
    }

    public function messages(): array
    {
        return ['codigo.regex' => 'El código de verificación no tiene un formato válido.'];
    }
}
