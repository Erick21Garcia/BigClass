<?php

namespace Modules\Academic\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => [
                'required',
                'integer',
                'exists:students,id',
                Rule::unique('enrollments', 'student_id')->where(fn ($query) =>
                    $query
                        ->where('career_id', $this->career_id)
                        ->where('academic_period_id', $this->academic_period_id)
                ),
            ],
            'career_id'          => ['required', 'integer', 'exists:careers,id'],
            'semester_id'        => ['required', 'integer', 'exists:semesters,id'],
            'academic_period_id' => ['required', 'integer', 'exists:academic_periods,id'],
            'enrollment_date'    => ['required', 'date'],
            'type'               => ['required', Rule::in(['regular', 'extraordinary', 'special'])],
            'status'             => ['required', Rule::in(['registered', 'active', 'withdrawn', 'completed'])],
            'curricula_ids'      => ['required', 'array', 'min:1'],
            'curricula_ids.*'    => [
                'integer',
                Rule::exists('curricula', 'id')->where('active', true),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required'         => 'El estudiante es obligatorio.',
            'student_id.exists'           => 'El estudiante seleccionado no existe.',
            'student_id.unique'           => 'El estudiante ya tiene una matrícula en esta carrera para el período académico seleccionado.',
            'career_id.required'          => 'La carrera es obligatoria.',
            'career_id.exists'            => 'La carrera seleccionada no existe.',
            'semester_id.required'        => 'El semestre es obligatorio.',
            'semester_id.exists'          => 'El semestre seleccionado no existe.',
            'academic_period_id.required' => 'El período académico es obligatorio.',
            'academic_period_id.exists'   => 'El período académico seleccionado no existe.',
            'enrollment_date.required'    => 'La fecha de matrícula es obligatoria.',
            'enrollment_date.date'        => 'La fecha de matrícula no tiene un formato válido.',
            'type.required'               => 'El tipo de matrícula es obligatorio.',
            'type.in'                     => 'El tipo debe ser: regular, extraordinary o special.',
            'status.required'             => 'El estado es obligatorio.',
            'status.in'                   => 'El estado debe ser: registered, active, withdrawn o completed.',
            'curricula_ids.required'      => 'Debes seleccionar al menos una materia.',
            'curricula_ids.min'           => 'Debes seleccionar al menos una materia.',
            'curricula_ids.*.integer'     => 'Una o más materias seleccionadas no son válidas.',
            'curricula_ids.*.exists'      => 'Una o más materias seleccionadas no existen o están inactivas.',
        ];
    }
}