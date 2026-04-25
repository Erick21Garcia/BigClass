<?php

namespace Modules\Institucion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFacultyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'institution_id' => ['required', 'integer', 'exists:institutions,id'],
            'name'           => ['required', 'string', 'max:255'],
            'code'           => ['nullable', 'string', 'max:50', 'unique:faculties,code'],
            'description'    => ['nullable', 'string'],
            'dean_name'      => ['nullable', 'string', 'max:255'],
            'active'         => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'institution_id.required' => 'La institución es obligatoria.',
            'institution_id.exists'   => 'La institución seleccionada no existe.',
            'name.required'           => 'El nombre de la facultad es obligatorio.',
            'code.unique'             => 'El código ya está en uso por otra facultad.',
        ];
    }
}