<?php

namespace Modules\Academic\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'curricula_id' => ['required', 'integer', 'exists:curricula,id'],
            'teacher_id'   => ['required', 'integer', 'exists:teachers,id'],
            'name'         => ['required', 'string', 'max:100'],
            'quota'        => ['required', 'integer', 'min:1', 'max:500'],
            'active'       => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'curricula_id.required' => 'La materia es obligatoria.',
            'curricula_id.exists'   => 'La materia seleccionada no existe.',
            'teacher_id.required'   => 'El docente es obligatorio.',
            'teacher_id.exists'     => 'El docente seleccionado no existe.',
            'name.required'         => 'El nombre de la sección es obligatorio.',
            'name.max'              => 'El nombre no puede superar los 100 caracteres.',
            'quota.required'        => 'El cupo es obligatorio.',
            'quota.min'             => 'El cupo mínimo es 1.',
            'quota.max'             => 'El cupo máximo es 500.',
        ];
    }
}