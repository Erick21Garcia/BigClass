<?php

namespace Modules\Institucion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInstitutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'type'     => ['required', 'string', 'max:100'],
            'code'     => ['nullable', 'string', 'max:50', 'unique:institutions,code'],
            'acronym'  => ['nullable', 'string', 'max:20'],
            'email'    => ['nullable', 'email', 'max:255'],
            'phone'    => ['nullable', 'string', 'max:20'],
            'address'  => ['nullable', 'string', 'max:255'],
            'city'     => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'country'  => ['nullable', 'string', 'max:100'],
            'active'   => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la institución es obligatorio.',
            'name.max'      => 'El nombre no puede superar los 255 caracteres.',
            'type.required' => 'El tipo de institución es obligatorio.',
            'code.unique'   => 'El código ya está en uso por otra institución.',
            'email.email'   => 'El correo electrónico no tiene un formato válido.',
        ];
    }
}