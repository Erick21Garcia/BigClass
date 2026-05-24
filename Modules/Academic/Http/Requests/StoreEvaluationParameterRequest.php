<?php

namespace Modules\Academic\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEvaluationParameterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'academic_period_id' => ['required', 'integer', 'exists:academic_periods,id'],
            'curriculum_id'      => ['nullable', 'integer', 'exists:curricula,id'],
            'name'               => ['required', 'string', 'max:100'],
            'percentage'         => ['required', 'numeric', 'min:0.01', 'max:100'],
            'is_final'           => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'academic_period_id.required' => 'El período académico es obligatorio.',
            'academic_period_id.exists'   => 'El período académico no existe.',
            'curriculum_id.exists'        => 'La materia seleccionada no existe.',
            'name.required'               => 'El nombre del parámetro es obligatorio.',
            'percentage.required'         => 'El porcentaje es obligatorio.',
            'percentage.min'              => 'El porcentaje debe ser mayor a 0.',
            'percentage.max'              => 'El porcentaje no puede superar 100.',
        ];
    }
}