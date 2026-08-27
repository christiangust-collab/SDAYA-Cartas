<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PreviewCorrelativoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'area' => ['required', 'integer', Rule::exists('areas', 'id')->where('activo', true)],
            'tipo' => ['required', 'integer', Rule::exists('tipos', 'id')->where('activo', true)],
            'anio' => ['required', 'integer', 'between:2020,2100'],
        ];
    }
}
