<?php

namespace Modules\Admissions\Services;

use Illuminate\Support\Facades\DB;
use Modules\Academic\Models\AcademicPeriod;
use Modules\Academic\Models\Enrollment;
use Modules\Admissions\Models\Applicant;
use Modules\Institucion\Models\Semester;
use Modules\People\Models\Person;
use Modules\People\Models\Student;

class AdmissionConversionService
{
    /**
     * Conversión automática al aprobar — Punto 5. Crea Person + Student +
     * Enrollment real, reutilizando el User que el aspirante ya tenía
     * desde el registro (Punto 1). Todo en una transacción: si algo
     * falla a medias, no se queda un Person huérfano sin Student.
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

            $activePeriod = AcademicPeriod::active()->first();

            if (! $activePeriod) {
                throw new \RuntimeException('No hay un periodo académico activo — no se puede crear la matrícula.');
            }

            $firstSemester = Semester::where('career_id', $applicant->career_id)
                ->where('number', 1)
                ->first();

            if (! $firstSemester) {
                throw new \RuntimeException("La carrera '{$applicant->career->name}' no tiene un Semestre 1 configurado.");
            }

            // NOTA (pendiente marcado en el diseño): esto crea la
            // matrícula general, pero NO las EnrollmentItem por materia
            // — esas requieren asignar Section (paralelo) por materia,
            // lo cual el staff completa manualmente después desde el
            // flujo de matrícula que ya existe en el ISI.
            Enrollment::create([
                'student_id'          => $student->id,
                'career_id'           => $applicant->career_id,
                'semester_id'         => $firstSemester->id,
                'academic_period_id'  => $activePeriod->id,
                'enrollment_date'     => now(),
                'type'                => 'regular',
                'status'              => 'active',
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