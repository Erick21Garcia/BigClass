<?php

namespace Modules\Academic\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JustifyAttendanceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'justified'          => ['required', 'boolean'],
            'justification_note' => ['nullable', 'string', 'max:300'],
        ];
    }

    public function messages(): array
    {
        return [
            'justified.required' => 'El campo justificado es obligatorio.',
            'justified.boolean'  => 'El campo justificado debe ser verdadero o falso.',
        ];
    }
}