<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;

final class GuardarUsuarioRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => Str::lower(trim((string) $this->input('email'))),
            ]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', new Enum(RolUsuario::class)],
            'empresa_id' => ['required', 'integer', 'exists:empresas,id'],
            'cargo' => ['nullable', 'string', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'activo' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre completo del usuario es obligatorio.',
            'name.max' => 'El nombre no puede superar los 150 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El formato del correo electrónico no es válido.',
            'email.unique' => 'Este correo electrónico ya está registrado en el sistema.',
            'password.required' => 'La contraseña inicial es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'role.required' => 'Debes seleccionar un rol para el usuario.',
            'empresa_id.required' => 'Debes asignar una empresa al usuario.',
            'empresa_id.exists' => 'La empresa seleccionada no es válida.',
        ];
    }
}
