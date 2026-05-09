<?php

namespace Modules\Academic\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAcademicPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:255', 'unique:academic_periods,name'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after:start_date'],
            'active'  => ['sometimes', 'boolean'],
            'status'     => ['required', 'string', 'in:draft,active,closed'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'       => 'El nombre es obligatorio.',
            'name.unique'         => 'Ya existe un período con ese nombre.',
            'start_date.required' => 'La fecha de inicio es obligatoria.',
            'start_date.date'     => 'La fecha de inicio no es válida.',
            'end_date.required'   => 'La fecha de fin es obligatoria.',
            'end_date.date'       => 'La fecha de fin no es válida.',
            'end_date.after'      => 'La fecha de fin debe ser posterior a la de inicio.',
            'status.required'     => 'El estado es obligatorio.',
            'status.in'           => 'El estado debe ser: borrador, activo o cerrado.',
        ];
    }
}