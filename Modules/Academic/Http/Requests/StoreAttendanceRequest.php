<?php

namespace Modules\Academic\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'date'                         => ['required', 'date'],
            'records'                      => ['required', 'array', 'min:1'],
            'records.*.student_id'         => ['required', 'integer', 'exists:students,id'],
            'records.*.status'             => ['required', 'in:present,absent,late'],
            'records.*.justified'          => ['boolean'],
            'records.*.justification_note' => ['nullable', 'string', 'max:300'],
            'schedule_id'                  => ['nullable', 'integer', 'exists:schedules,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required'                 => 'La fecha es obligatoria.',
            'date.date'                     => 'La fecha no es válida.',
            'records.required'              => 'Debe incluir al menos un registro.',
            'records.*.student_id.required' => 'El estudiante es obligatorio.',
            'records.*.student_id.exists'   => 'El estudiante no existe.',
            'records.*.status.required'     => 'El estado de asistencia es obligatorio.',
            'records.*.status.in'           => 'El estado debe ser: present, absent o late.',
        ];
    }
}