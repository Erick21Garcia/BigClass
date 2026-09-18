<?php

namespace Modules\Admissions\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApplicantProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        // El propio aspirante autenticado edita su propia postulación —
        // no hay "otro" aspirante que pueda tocar, se resuelve por sesión.
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name'              => ['required', 'string', 'max:255'],
            'second_name'             => ['nullable', 'string', 'max:255'],
            'first_surname'           => ['required', 'string', 'max:255'],
            'second_surname'          => ['nullable', 'string', 'max:255'],
            'identification_number'   => ['required', 'string', 'max:20'],
            'phone'                   => ['nullable', 'string', 'max:20'],
            'cellphone'               => ['required', 'string', 'max:20'],
            'birthdate'               => ['required', 'date', 'before:today'],
            'place_birth'             => ['required', 'string', 'max:255'],
            'main_street'             => ['required', 'string', 'max:255'],
            'secondary_street'        => ['nullable', 'string', 'max:255'],
            'neighborhood'            => ['nullable', 'string', 'max:255'],
            'reference'               => ['nullable', 'string', 'max:255'],
            'marital_status_id'       => ['required', 'integer'],
            'type_identification_id'  => ['required', 'integer'],
            'sex_id'                  => ['required', 'integer'],
            'nationality_id'          => ['required', 'integer'],
            'education_level_id'      => ['required', 'integer'],
            'countries_id'            => ['required', 'integer'],
            'provinces_id'            => ['required', 'integer'],
            'cities_id'               => ['required', 'integer'],
        ];
    }
}