<?php

namespace Modules\Academic\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file'               => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
            'semester_id'        => ['required', 'integer', 'exists:semesters,id'],
            'academic_period_id' => ['required', 'integer', 'exists:academic_periods,id'],
            'type'               => ['required', 'in:regular,extraordinary,special'],
            'status'             => ['required', 'in:registered,active'],
            'enrollment_date'    => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required'               => 'El archivo Excel es obligatorio.',
            'file.mimes'                  => 'El archivo debe ser .xlsx o .xls.',
            'file.max'                    => 'El archivo no puede superar los 5 MB.',
            'semester_id.required'        => 'El semestre es obligatorio.',
            'academic_period_id.required' => 'El período académico es obligatorio.',
            'type.required'               => 'El tipo de matrícula es obligatorio.',
            'type.in'                     => 'El tipo debe ser regular, extraordinary o special.',
            'status.required'             => 'El estado es obligatorio.',
            'status.in'                   => 'El estado debe ser registered o active.',
            'enrollment_date.required'    => 'La fecha de matrícula es obligatoria.',
        ];
    }
}