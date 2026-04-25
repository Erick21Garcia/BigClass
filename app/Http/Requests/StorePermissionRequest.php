<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:permissions,name',
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