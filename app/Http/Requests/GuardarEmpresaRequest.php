<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Empresa;
use Illuminate\Foundation\Http\FormRequest;

final class GuardarEmpresaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $empresa = $this->route('empresa');

        return $empresa instanceof Empresa
            ? ($this->user()?->can('update', $empresa) ?? false)
            : ($this->user()?->can('create', Empresa::class) ?? false);
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'nit' => ['nullable', 'string', 'max:50'],
            'direccion' => ['nullable', 'string', 'max:250'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'correo' => ['nullable', 'email', 'max:150'],
            'sitio_web' => ['nullable', 'string', 'max:150'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'eliminar_logo' => ['nullable', 'boolean'],
            'activo' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre o razón social de la empresa es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 150 caracteres.',
            'correo.email' => 'El correo institucional debe ser una dirección de email válida.',
            'logo.image' => 'El archivo seleccionado debe ser una imagen válida (PNG, JPG, WebP).',
            'logo.max' => 'La imagen del logo no puede exceder 2 MB.',
        ];
    }
}