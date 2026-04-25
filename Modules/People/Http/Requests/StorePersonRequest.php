<?php

namespace Modules\People\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name'             => ['required', 'string', 'max:100'],
            'second_name'            => ['nullable', 'string', 'max:100'],
            'first_surname'          => ['required', 'string', 'max:100'],
            'second_surname'         => ['nullable', 'string', 'max:100'],
            'identification_number'  => ['required', 'string', 'max:20', 'unique:people,identification_number'],
            'phone'                  => ['nullable', 'string', 'max:20'],
            'cellphone'              => ['nullable', 'string', 'max:20'],
            'birthdate'              => ['nullable', 'date'],
            'place_birth'            => ['nullable', 'string', 'max:150'],

            'main_street'            => ['nullable', 'string', 'max:150'],
            'secondary_street'       => ['nullable', 'string', 'max:150'],
            'neighborhood'           => ['nullable', 'string', 'max:150'],
            'reference'              => ['nullable', 'string', 'max:255'],

            'sex_id'                 => ['required', 'exists:sexes,id'],
            'type_identification_id' => ['required', 'exists:type_identifications,id'],
            'marital_status_id'      => ['nullable', 'exists:marital_statuses,id'],
            'nationality_id'         => ['nullable', 'exists:nationalities,id'],
            'education_level_id'     => ['nullable', 'exists:education_levels,id'],
            'countries_id'           => ['nullable', 'exists:countries,id'],
            'provinces_id'           => ['nullable', 'exists:provinces,id'],
            'cities_id'              => ['nullable', 'exists:cities,id'],
        ];
    }
}