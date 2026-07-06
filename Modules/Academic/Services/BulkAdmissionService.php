<?php

namespace Modules\Academic\Services;

use Illuminate\Support\Facades\DB;
use Modules\Academic\Imports\BulkAdmissionImport;
use Modules\Institucion\Models\Curriculum;
use Modules\People\Models\Person;
use Modules\People\Models\Student;
use Modules\People\Services\PersonService;
use Modules\People\Services\StudentService;
use Maatwebsite\Excel\Facades\Excel;

class BulkAdmissionService
{
    public function __construct(
        private PersonService $personService,
        private StudentService $studentService,
        private EnrollmentService $enrollmentService,
    ) {}

    public function bulkPreview($file, int $semesterId, int $careerId, int $academicPeriodId): array
    {
        $import = new BulkAdmissionImport();
        Excel::import($import, $file);

        $rows = $import->getRows();

        $curriculaByCode = Curriculum::where('career_id', $careerId)
            ->where('active', true)
            ->with('subject')
            ->get()
            ->keyBy('subject.code');

        $alreadyEnrolledStudentIds = \Modules\Academic\Models\Enrollment::where('semester_id', $semesterId)
            ->where('career_id', $careerId)
            ->where('academic_period_id', $academicPeriodId)
            ->whereIn('status', ['active', 'registered'])
            ->pluck('student_id')
            ->flip();

        return $rows->map(function ($row) use ($curriculaByCode, $alreadyEnrolledStudentIds, $careerId) {
            $errors = [];

            // 1. Validar campos obligatorios de Person
            if ($row['first_name'] === '') {
                $errors[] = 'Falta el nombre (first_name).';
            }
            if ($row['first_surname'] === '') {
                $errors[] = 'Falta el apellido (first_surname).';
            }
            if (! $row['sex_id']) {
                $errors[] = 'Falta sex_id.';
            }
            if (! $row['type_identification_id']) {
                $errors[] = 'Falta type_identification_id.';
            }
            if ($row['enrollment_number'] === '') {
                $errors[] = 'Falta enrollment_number.';
            }

            // 2. ¿La persona ya existe?
            $person = Person::where('identification_number', $row['cedula'])->first();
            $personExists = (bool) $person;

            // 3. Si la persona existe, ¿ya tiene perfil de Student?
            $student = $person
                ? Student::where('person_id', $person->id)->first()
                : null;
            $studentExists = (bool) $student;

            // 4. Si NO existe la persona: validar que la cédula no esté ya usada por error de datos
            if (! $personExists) {
                // nada más que validar aquí; se creará
            }

            // 5. Si el enrollment_number ya está en uso por OTRO estudiante, error
            if (! $studentExists && $row['enrollment_number'] !== '') {
                $clash = Student::where('enrollment_number', $row['enrollment_number'])->exists();
                if ($clash) {
                    $errors[] = "El número de matrícula {$row['enrollment_number']} ya está en uso.";
                }
            }

            // 6. ¿Ya matriculado en este semestre/período?
            if ($studentExists && isset($alreadyEnrolledStudentIds[$student->id])) {
                $errors[] = 'Ya tiene una matrícula activa en este semestre y período.';
            }

            // 7. Resolver materias
            $curriculaIds = [];
            $notFound = [];
            foreach ($row['codigos_materias'] as $code) {
                $curriculum = $curriculaByCode->get($code);
                if (! $curriculum) {
                    $notFound[] = $code;
                } else {
                    $curriculaIds[] = $curriculum->id;
                }
            }
            if (! empty($notFound)) {
                $errors[] = 'Materias no encontradas: '.implode(', ', $notFound);
            }
            if (empty($row['codigos_materias'])) {
                $errors[] = 'No se especificaron materias.';
            }

            // 8. Prerequisitos (solo si la persona/estudiante ya existe y no hay otros errores)
            if (empty($errors) && $studentExists && ! empty($curriculaIds)) {
                try {
                    $this->enrollmentService->validatePrerequisitesPublic($student->id, $curriculaIds);
                } catch (\DomainException $e) {
                    $errors[] = $e->getMessage();
                }
            }

            return [
                'cedula'            => $row['cedula'],
                'enrollment_number' => $row['enrollment_number'],
                'full_name'         => trim("{$row['first_name']} {$row['second_name']} {$row['first_surname']} {$row['second_surname']}"),
                'person_exists'     => $personExists,
                'student_exists'    => $studentExists,
                'person_id'         => $person?->id,
                'student_id'        => $student?->id,
                'career_id'         => $careerId,
                'raw'               => $row,
                'curricula_ids'     => $curriculaIds,
                'codigos_materias'  => $row['codigos_materias'],
                'can_enroll'        => empty($errors),
                'errors'            => $errors,
            ];
        })->values()->all();
    }

    /**
     * Ejecuta la cascada Person → Student → Enrollment para cada fila válida.
     * Cada fila corre en su propia transacción: si algo falla, no deja
     * registros parciales (persona sin estudiante, estudiante sin matrícula).
     */
    public function bulkCreate(array $rows, array $sharedData): array
    {
        $created  = [];
        $skipped  = [];

        foreach ($rows as $row) {
            if (! $row['can_enroll']) {
                $skipped[] = [
                    'cedula'    => $row['cedula'],
                    'full_name' => $row['full_name'],
                    'errors'    => $row['errors'],
                ];
                continue;
            }

            try {
                $result = DB::transaction(function () use ($row, $sharedData) {
                    // 1. Persona — buscar o crear
                    $person = $row['person_exists']
                        ? Person::find($row['person_id'])
                        : $this->personService->create([
                            'first_name'             => $row['raw']['first_name'],
                            'second_name'            => $row['raw']['second_name'],
                            'first_surname'          => $row['raw']['first_surname'],
                            'second_surname'         => $row['raw']['second_surname'],
                            'identification_number'  => $row['cedula'],
                            'phone'                  => $row['raw']['phone'],
                            'cellphone'              => $row['raw']['cellphone'],
                            'birthdate'              => $row['raw']['birthdate'],
                            'place_birth'            => $row['raw']['place_birth'],
                            'main_street'            => $row['raw']['main_street'],
                            'secondary_street'       => $row['raw']['secondary_street'],
                            'neighborhood'           => $row['raw']['neighborhood'],
                            'reference'              => $row['raw']['reference'],
                            'sex_id'                 => $row['raw']['sex_id'],
                            'type_identification_id' => $row['raw']['type_identification_id'],
                            'marital_status_id'      => $row['raw']['marital_status_id'],
                            'nationality_id'         => $row['raw']['nationality_id'],
                            'education_level_id'     => $row['raw']['education_level_id'],
                            'countries_id'            => $row['raw']['countries_id'],
                            'provinces_id'            => $row['raw']['provinces_id'],
                            'cities_id'               => $row['raw']['cities_id'],
                        ]);

                    // 2. Estudiante — buscar o crear
                    $student = $row['student_exists']
                        ? Student::find($row['student_id'])
                        : $this->studentService->create([
                            'person_id'         => $person->id,
                            'enrollment_number' => $row['enrollment_number'],
                            'active'            => true,
                        ]);

                    // 3. Matrícula
                    $enrollment = $this->enrollmentService->create([
                        'student_id'         => $student->id,
                        'career_id'          => $row['career_id'],
                        'semester_id'        => $sharedData['semester_id'],
                        'academic_period_id' => $sharedData['academic_period_id'],
                        'enrollment_date'    => $sharedData['enrollment_date'],
                        'type'               => $sharedData['type'],
                        'status'             => $sharedData['status'],
                        'curricula_ids'      => $row['curricula_ids'],
                    ]);

                    return [
                        'person_created'  => ! $row['person_exists'],
                        'student_created' => ! $row['student_exists'],
                        'enrollment_id'   => $enrollment->id,
                    ];
                });

                $created[] = [
                    'cedula'           => $row['cedula'],
                    'full_name'        => $row['full_name'],
                    'person_created'   => $result['person_created'],
                    'student_created'  => $result['student_created'],
                ];
            } catch (\Throwable $e) {
                $skipped[] = [
                    'cedula'    => $row['cedula'],
                    'full_name' => $row['full_name'],
                    'errors'    => [$e instanceof \DomainException ? $e->getMessage() : 'Error inesperado al procesar esta fila.'],
                ];
            }
        }

        return [
            'created_count' => count($created),
            'skipped_count' => count($skipped),
            'created'       => $created,
            'skipped'       => $skipped,
        ];
    }
}