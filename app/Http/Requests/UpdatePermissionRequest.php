<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $permissionId = $this->route('permission')->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('permissions', 'name')->ignore($permissionId),
                'regex:/^[a-z]+(\.[a-z]+)*$/',
            ],
            'description' => [
                'nullable',
                'string',
                'max:500',
            ],
            'guard_name' => [
                'required',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del permiso es obligatorio.',
            'name.unique' => 'Este permiso ya existe.',
            'name.regex' => 'El nombre debe tener el formato: recurso.accion (ej: users.create)',
            'guard_name.required' => 'El guard name es obligatorio.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre del permiso',
            'description' => 'descripción',
            'guard_name' => 'guard name',
        ];
    }
}