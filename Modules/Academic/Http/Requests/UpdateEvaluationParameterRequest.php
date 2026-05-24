<?php

namespace Modules\Academic\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEvaluationParameterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:100'],
            'percentage' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'is_final'   => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'       => 'El nombre del parámetro es obligatorio.',
            'percentage.required' => 'El porcentaje es obligatorio.',
            'percentage.min'      => 'El porcentaje debe ser mayor a 0.',
            'percentage.max'      => 'El porcentaje no puede superar 100.',
        ];
    }
}