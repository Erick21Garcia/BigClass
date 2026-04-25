<?php

namespace Modules\People\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'person_id'         => ['required', 'integer', 'exists:people,id', 'unique:students,person_id'],
            'enrollment_number' => ['required', 'string', 'max:50', 'unique:students,enrollment_number'],
            'active'            => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'person_id.required'         => 'La persona es obligatoria.',
            'person_id.exists'           => 'La persona seleccionada no existe.',
            'person_id.unique'           => 'Esta persona ya está registrada como estudiante.',
            'enrollment_number.required' => 'El número de matrícula es obligatorio.',
            'enrollment_number.max'      => 'El número de matrícula no puede superar los 50 caracteres.',
            'enrollment_number.unique'   => 'Este número de matrícula ya está en uso.',
        ];
    }
}