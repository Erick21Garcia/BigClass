<?php

namespace Modules\People\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'institution_id' => ['nullable', 'integer', 'exists:institutions,id'],
            'position'       => ['nullable', 'string', 'max:255'],
            'active'         => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'institution_id.exists' => 'La institución seleccionada no existe.',
            'active.required'       => 'El estado es obligatorio.',
        ];
    }
}