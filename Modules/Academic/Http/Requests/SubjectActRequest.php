<?php

namespace Modules\Academic\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubjectActRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'section_id'         => ['required', 'integer', 'exists:sections,id'],
            'academic_period_id' => ['required', 'integer', 'exists:academic_periods,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'section_id.required'         => 'La sección es obligatoria.',
            'section_id.exists'           => 'La sección seleccionada no existe.',
            'academic_period_id.required' => 'El período académico es obligatorio.',
            'academic_period_id.exists'   => 'El período académico seleccionado no existe.',
        ];
    }
}