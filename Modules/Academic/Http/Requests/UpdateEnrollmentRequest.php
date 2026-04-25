<?php

namespace Modules\Academic\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $enrollmentId = $this->route('enrollment')?->id;

        return [
            'student_id'         => [
                'required',
                'integer',
                'exists:students,id',
                Rule::unique('enrollments', 'student_id')
                    ->where(function ($query) {
                        return $query
                            ->where('career_id', $this->career_id)
                            ->where('academic_period_id', $this->academic_period_id);
                    })
                    ->ignore($enrollmentId),
            ],
            'career_id'          => ['required', 'integer', 'exists:careers,id'],
            'semester_id'        => ['required', 'integer', 'exists:semesters,id'],
            'academic_period_id' => ['required', 'integer', 'exists:academic_periods,id'],
            'enrollment_date'    => ['required', 'date'],
            'type'               => ['required', Rule::in(['regular', 'extraordinary', 'special'])],
            'status'             => ['required', Rule::in(['registered', 'active', 'withdrawn', 'completed'])],
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.unique'          => 'El estudiante ya tiene una matrícula en esta carrera para el período académico seleccionado.',
            'student_id.required'        => 'El estudiante es obligatorio.',
            'student_id.exists'          => 'El estudiante seleccionado no existe.',
            'career_id.required'         => 'La carrera es obligatoria.',
            'career_id.exists'           => 'La carrera seleccionada no existe.',
            'semester_id.required'       => 'El semestre es obligatorio.',
            'semester_id.exists'         => 'El semestre seleccionado no existe.',
            'academic_period_id.required'=> 'El período académico es obligatorio.',
            'academic_period_id.exists'  => 'El período académico seleccionado no existe.',
            'enrollment_date.required'   => 'La fecha de matrícula es obligatoria.',
            'enrollment_date.date'       => 'La fecha de matrícula no tiene un formato válido.',
            'type.required'              => 'El tipo de matrícula es obligatorio.',
            'type.in'                    => 'El tipo debe ser: regular, extraordinary o special.',
            'status.required'            => 'El estado es obligatorio.',
            'status.in'                  => 'El estado debe ser: registered, active, withdrawn o completed.',
        ];
    }
}