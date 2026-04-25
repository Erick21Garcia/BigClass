<?php

namespace Modules\Institucion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code'        => ['nullable', 'string', 'max:50', Rule::unique('subjects', 'code')->ignore($this->subject)],
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'credits'     => ['required', 'integer', 'min:1'],
            'active'      => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'    => 'El nombre de la materia es obligatorio.',
            'code.unique'      => 'El código ya está en uso por otra materia.',
            'credits.required' => 'Los créditos son obligatorios.',
            'credits.min'      => 'Los créditos deben ser al menos 1.',
        ];
    }
}