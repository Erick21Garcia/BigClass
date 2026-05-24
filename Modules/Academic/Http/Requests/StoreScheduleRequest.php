<?php

namespace Modules\Academic\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'section_id'     => ['required', 'integer', 'exists:sections,id'],
            'classroom_id'   => ['required', 'integer', 'exists:classrooms,id'],
            'day_of_week'    => ['required', 'integer', 'min:0', 'max:6'],
            'start_time'     => ['required', 'date_format:H:i'],
            'end_time'       => ['required', 'date_format:H:i', 'after:start_time'],
            'is_recurring'   => ['boolean'],
            'specific_date'  => ['nullable', 'date', 'required_if:is_recurring,false'],
            'recurrence_end' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'section_id.required'          => 'La sección es obligatoria.',
            'section_id.exists'            => 'La sección seleccionada no existe.',
            'classroom_id.required'        => 'El aula es obligatoria.',
            'classroom_id.exists'          => 'El aula seleccionada no existe.',
            'day_of_week.required'         => 'El día de la semana es obligatorio.',
            'day_of_week.min'              => 'El día de la semana no es válido.',
            'day_of_week.max'              => 'El día de la semana no es válido.',
            'start_time.required'          => 'La hora de inicio es obligatoria.',
            'start_time.date_format'       => 'La hora de inicio debe tener el formato HH:MM.',
            'end_time.required'            => 'La hora de fin es obligatoria.',
            'end_time.date_format'         => 'La hora de fin debe tener el formato HH:MM.',
            'end_time.after'               => 'La hora de fin debe ser posterior a la de inicio.',
            'specific_date.required_if'    => 'La fecha específica es obligatoria para horarios no recurrentes.',
        ];
    }
}