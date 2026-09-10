<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Documento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class GuardarDocumentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $documento = $this->route('documento');

        return $documento instanceof Documento
            ? ($this->user()?->can('update', $documento) ?? false)
            : ($this->user()?->can('create', Documento::class) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'asunto' => $this->textoOpcional('asunto'),
            'destinatario' => $this->textoOpcional('destinatario'),
            'lugar' => $this->textoOpcional('lugar'),
            'alineacion_encabezado' => in_array($this->input('alineacion_encabezado'), ['left', 'center', 'right'], true)
                ? $this->input('alineacion_encabezado')
                : 'right',
            'alineacion_pie_firma' => in_array($this->input('alineacion_pie_firma'), ['left', 'center', 'right'], true)
                ? $this->input('alineacion_pie_firma')
                : (in_array($this->input('alineacion_encabezado'), ['left', 'center', 'right'], true) ? $this->input('alineacion_encabezado') : 'right'),
        ]);
    }

    private function textoOpcional(string $campo): ?string
    {
        $valor = trim((string) $this->input($campo));

        return $valor === '' ? null : $valor;
    }

    public function rules(): array
    {
        return [
            'area_id' => [
                'required',
                'integer',
                Rule::exists('areas', 'id')->where('activo', true),
            ],
            'tipo_id' => [
                'required',
                'integer',
                Rule::exists('tipos', 'id')->where('activo', true),
            ],
            'empresa_id' => [
                'nullable',
                'integer',
                Rule::exists('empresas', 'id')->where('activo', true),
            ],
            'fecha_documento' => ['required', 'date_format:Y-m-d', 'after_or_equal:2020-01-01', 'before_or_equal:+1 year'],
            'lugar' => ['nullable', 'string', 'max:80'],
            'alineacion_encabezado' => ['required', Rule::in(['left', 'center', 'right'])],
            'alineacion_pie_firma' => ['required', Rule::in(['left', 'center', 'right'])],
            'asunto' => ['nullable', 'string', 'max:250'],
            'destinatario' => ['nullable', 'string', 'max:250'],
            'firmante_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'datos_firmante' => ['nullable', 'array'],
            'datos_firmante.nombre' => ['nullable', 'string', 'max:150'],
            'datos_firmante.cargo' => ['nullable', 'string', 'max:150'],
            'datos_firmante.empresa' => ['nullable', 'string', 'max:150'],
            'datos_firmante.correo' => ['nullable', 'email', 'max:150'],
            'datos_firmante.telefono' => ['nullable', 'string', 'max:50'],
            'contenido' => ['required', 'string', 'max:200000'],
            'accion' => ['required', Rule::in(['guardar', 'emitir'])],
        ];
    }

    public function after(): array
    {
        return [
            static function (Validator $validator): void {
                $contenido = (string) ($validator->getData()['contenido'] ?? '');
                $texto = trim(html_entity_decode(strip_tags($contenido), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

                if ($texto === '') {
                    $validator->errors()->add('contenido', 'Escribe el contenido del documento.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'area_id.exists' => 'Selecciona un área activa.',
            'tipo_id.exists' => 'Selecciona un tipo activo.',
            'fecha_documento.before_or_equal' => 'La fecha no puede superar un año desde hoy.',
        ];
    }
}
