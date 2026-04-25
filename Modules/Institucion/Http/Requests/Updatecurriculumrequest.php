<?php

namespace Modules\Institucion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCurriculumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'career_id'    => ['required', 'integer', 'exists:careers,id'],
            'subject_id'   => [
                'required',
                'integer',
                'exists:subjects,id',
                Rule::unique('curricula')
                    ->where(fn ($query) => $query
                        ->where('career_id', $this->career_id)
                        ->where('semester_id', $this->semester_id)
                    )
                    ->ignore($this->curriculum),
            ],
            'semester_id'  => ['required', 'integer', 'exists:semesters,id'],
            'is_mandatory' => ['boolean'],
            'active'       => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'career_id.required'   => 'La carrera es obligatoria.',
            'career_id.exists'     => 'La carrera seleccionada no existe.',
            'subject_id.required'  => 'La materia es obligatoria.',
            'subject_id.exists'    => 'La materia seleccionada no existe.',
            'subject_id.unique'    => 'Esta materia ya está asignada a ese semestre en esa carrera.',
            'semester_id.required' => 'El semestre es obligatorio.',
            'semester_id.exists'   => 'El semestre seleccionado no existe.',
        ];
    }
}