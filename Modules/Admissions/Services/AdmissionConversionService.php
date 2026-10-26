<?php

namespace Modules\Admissions\Services;

use Illuminate\Support\Facades\DB;
use Modules\Admissions\Models\Applicant;
use Modules\People\Models\Person;
use Modules\People\Models\Student;

class AdmissionConversionService
{
    /**
     * Conversión automática al aprobar. Crea Person + Student
     * reutilizando el User que el aspirante ya tenía desde el registro.
     *
     * NO crea la matrícula: esa la hace el staff con el flujo manual del ISI
     * (/enrollments/create), que valida prerrequisitos, cupos y horarios.
     * Todo en una transacción: si algo falla, no queda un Person huérfano.
     */
    public function convertToStudent(Applicant $applicant): Student
    {
        if ($applicant->status !== 'aprobada') {
            throw new \RuntimeException('Solo se puede convertir una postulación aprobada.');
        }

        if (Person::where('user_id', $applicant->user_id)->exists()) {
            throw new \RuntimeException('Este usuario ya tiene un Person asociado — no se puede convertir dos veces.');
        }

        return DB::transaction(function () use ($applicant) {
            $person = Person::create([
                'user_id'                => $applicant->user_id,
                'first_name'              => $applicant->first_name,
                'second_name'             => $applicant->second_name,
                'first_surname'           => $applicant->first_surname,
                'second_surname'          => $applicant->second_surname,
                'identification_number'   => $applicant->identification_number,
                'phone'                   => $applicant->phone,
                'cellphone'               => $applicant->cellphone,
                'birthdate'               => $applicant->birthdate,
                'place_birth'             => $applicant->place_birth,
                'main_street'             => $applicant->main_street,
                'secondary_street'        => $applicant->secondary_street,
                'neighborhood'            => $applicant->neighborhood,
                'reference'               => $applicant->reference,
                'marital_status_id'       => $applicant->marital_status_id,
                'type_identification_id'  => $applicant->type_identification_id,
                'sex_id'                  => $applicant->sex_id,
                'nationality_id'          => $applicant->nationality_id,
                'education_level_id'      => $applicant->education_level_id,
                'countries_id'            => $applicant->countries_id,
                'provinces_id'            => $applicant->provinces_id,
                'cities_id'               => $applicant->cities_id,
            ]);

            $student = Student::create([
                'person_id'         => $person->id,
                'enrollment_number' => $this->generateEnrollmentNumber(),
                'active'            => true,
            ]);

            // El User deja de ser 'aspirante' y pasa a ser 'estudiante'.
            $applicant->user->syncRoles(['estudiante']);

            return $student;
        });
    }

    private function generateEnrollmentNumber(): string
    {
        $year = now()->year;
        $sequence = Student::whereYear('created_at', $year)->count() + 1;

        return "{$year}-" . str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }
}