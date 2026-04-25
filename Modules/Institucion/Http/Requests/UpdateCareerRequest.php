<?php

namespace Modules\Institucion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCareerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'faculty_id'         => ['required', 'integer', 'exists:faculties,id'],
            'name'               => ['required', 'string', 'max:255'],
            'code'               => ['nullable', 'string', 'max:50', Rule::unique('careers', 'code')->ignore($this->career)],
            'description'        => ['nullable', 'string'],
            'modality'           => ['nullable', 'string', 'max:100'],
            'duration_semesters' => ['nullable', 'integer', 'min:1', 'max:20'],
            'title_awarded'      => ['nullable', 'string', 'max:255'],
            'active'             => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'faculty_id.required' => 'La facultad es obligatoria.',
            'faculty_id.exists'   => 'La facultad seleccionada no existe.',
            'name.required'       => 'El nombre de la carrera es obligatorio.',
            'code.unique'         => 'El código ya está en uso por otra carrera.',
            'duration_semesters.integer' => 'La duración debe ser un número entero.',
            'duration_semesters.min'     => 'La duración debe ser al menos 1 semestre.',
            'duration_semesters.max'     => 'La duración no puede superar los 20 semestres.',
        ];
    }
}