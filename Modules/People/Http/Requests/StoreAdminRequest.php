<?php

namespace Modules\People\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'person_id'      => ['required', 'integer', 'exists:people,id'],
            'institution_id' => ['nullable', 'integer', 'exists:institutions,id'],
            'position'       => ['nullable', 'string', 'max:255'],
            'active'         => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'person_id.required' => 'La persona es obligatoria.',
            'person_id.exists'   => 'La persona seleccionada no existe.',
            'institution_id.exists' => 'La institución seleccionada no existe.',
            'active.required'    => 'El estado es obligatorio.',
        ];
    }
}