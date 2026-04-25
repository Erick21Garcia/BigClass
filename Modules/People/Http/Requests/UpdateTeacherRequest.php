<?php

namespace Modules\People\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'institution_id'  => ['nullable', 'integer', 'exists:institutions,id'],
            'hire_date'       => ['nullable', 'date'],
            'academic_degree' => ['nullable', 'string', 'max:255'],
            'active'          => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'institution_id.exists' => 'La institución seleccionada no existe.',
            'hire_date.date'        => 'La fecha de ingreso no es válida.',
            'active.required'       => 'El estado es obligatorio.',
        ];
    }
}