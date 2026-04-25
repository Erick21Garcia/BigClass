<?php

namespace Modules\Institucion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSemesterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'career_id' => ['required', 'integer', 'exists:careers,id'],
            'number'    => ['required', 'integer', 'min:1', 'max:20'],
            'name'      => ['required', 'string', 'max:255'],
            'active'    => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'career_id.required' => 'La carrera es obligatoria.',
            'career_id.exists'   => 'La carrera seleccionada no existe.',
            'number.required'    => 'El número de semestre es obligatorio.',
            'number.integer'     => 'El número de semestre debe ser un entero.',
            'number.min'         => 'El número de semestre debe ser al menos 1.',
            'number.max'         => 'El número de semestre no puede superar 20.',
            'name.required'      => 'El nombre del semestre es obligatorio.',
        ];
    }
}