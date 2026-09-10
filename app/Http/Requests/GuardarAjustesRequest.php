<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\RolUsuario;
use Illuminate\Foundation\Http\FormRequest;

final class GuardarAjustesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === RolUsuario::ADMIN;
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'nombre_comercial' => ['nullable', 'string', 'max:150'],
            'nombre_aplicacion' => ['required', 'string', 'max:150'],
            'nit' => ['nullable', 'string', 'max:50'],
            'direccion' => ['nullable', 'string', 'max:250'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'correo' => ['nullable', 'email', 'max:150'],
            'sitio_web' => ['nullable', 'url', 'max:150'],
            'color_principal' => ['required', 'string', 'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/'],
            'color_secundario' => ['required', 'string', 'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'favicon' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,ico,svg', 'max:1024'],
            'logo_documentos' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg,pdf', 'max:5120'],
            'eliminar_logo' => ['nullable', 'boolean'],
            'eliminar_favicon' => ['nullable', 'boolean'],
            'eliminar_logo_documentos' => ['nullable', 'boolean'],
            'empresa_activa_id' => ['nullable', 'integer', 'exists:empresas,id'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la empresa es obligatorio.',
            'nombre_aplicacion.required' => 'El nombre visible de la aplicación es obligatorio.',
            'color_principal.regex' => 'El color principal debe ser un código hexadecimal válido (ej. #002b49).',
            'color_secundario.regex' => 'El color secundario debe ser un código hexadecimal válido (ej. #00487a).',
            'logo.image' => 'El archivo de logo debe ser una imagen válida.',
            'logo.max' => 'El logo no debe exceder 2 MB.',
            'favicon.max' => 'El favicon no debe exceder 1 MB.',
            'logo_documentos.max' => 'El membrete/logo para documentos no debe exceder 5 MB.',
            'logo_documentos.mimes' => 'El membrete debe estar en formato PNG, JPG, WebP, SVG o PDF.',
        ];
    }
}