<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Documento;
use Illuminate\Foundation\Http\FormRequest;

final class AnularDocumentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $documento = $this->route('documento');

        return $documento instanceof Documento
            && ($this->user()?->can('anular', $documento) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['motivo' => trim((string) $this->input('motivo'))]);
    }

    public function rules(): array
    {
        return ['motivo' => ['required', 'string', 'min:10', 'max:500']];
    }
}
