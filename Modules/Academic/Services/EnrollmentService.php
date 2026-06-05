<?php

namespace Modules\Academic\Services;

use Illuminate\Support\Collection;
use Modules\Academic\Imports\BulkEnrollmentImport;
use Modules\Academic\Models\Enrollment;
use Modules\Academic\Models\EnrollmentItem;
use Modules\Institucion\Models\Curriculum;
use Modules\People\Models\Student;
use Maatwebsite\Excel\Facades\Excel;

class EnrollmentService
{
    // =========================================================================
    // Matrícula individual (sin cambios)
    // =========================================================================

    /**
     * Crea la matrícula y sus items, validando prerequisitos por cada materia.
     */
    public function create(array $data): Enrollment
    {
        $curriculaIds = $data['curricula_ids'] ?? [];

        $this->validatePrerequisites($data['student_id'], $curriculaIds);

        $enrollment = Enrollment::create([
            'student_id'         => $data['student_id'],
            'career_id'          => $data['career_id'],
            'semester_id'        => $data['semester_id'],
            'academic_period_id' => $data['academic_period_id'],
            'enrollment_date'    => $data['enrollment_date'],
            'type'               => $data['type'],
            'status'             => $data['status'],
        ]);

        foreach ($curriculaIds as $curriculumId) {
            EnrollmentItem::create([
                'enrollment_id' => $enrollment->id,
                'curricula_id'  => $curriculumId,
                'status'        => 'en_curso',
                'active'        => true,
            ]);
        }

        return $enrollment;
    }

    /**
     * Actualiza solo los campos de cabecera de la matrícula.
     */
    public function update(Enrollment $enrollment, array $data): Enrollment
    {
        $enrollment->update([
            'academic_period_id' => $data['academic_period_id'] ?? $enrollment->academic_period_id,
            'enrollment_date'    => $data['enrollment_date']    ?? $enrollment->enrollment_date,
            'type'               => $data['type']               ?? $enrollment->type,
            'status'             => $data['status']             ?? $enrollment->status,
        ]);

        return $enrollment->fresh();
    }

    public function delete(Enrollment $enrollment): void
    {
        if ($enrollment->active) {
            throw new \DomainException('No se puede eliminar una matrícula activa.');
        }

        $enrollment->delete();
    }

    // =========================================================================
    // Matrícula masiva
    // =========================================================================

    /**
     * Lee el Excel y devuelve una previsualización fila por fila.
     *
     * Cada entrada del array resultado tiene:
     *   cedula, student_name, codigos_materias, curricula_ids,
     *   can_enroll (bool), errors (string[])
     *
     * @param  \Illuminate\Http\UploadedFile $file
     * @param  int  $semesterId
     * @param  int  $careerId       — se pasa desde el controller (viene del semester)
     * @param  int  $academicPeriodId
     */
    public function bulkPreview($file, int $semesterId, int $careerId, int $academicPeriodId): array
    {
        $import = new BulkEnrollmentImport();
        Excel::import($import, $file);

        $rows = $import->getRows();

        // Pre-cargar todos los curricula del semestre/carrera para búsqueda por código
        $curriculaByCode = Curriculum::where('career_id', $careerId)
            ->where('active', true)
            ->with('subject')
            ->get()
            ->keyBy('subject.code');

        // Estudiantes ya matriculados en este semestre/carrera/período
        $alreadyEnrolledStudentIds = Enrollment::where('semester_id', $semesterId)
            ->where('career_id', $careerId)
            ->where('academic_period_id', $academicPeriodId)
            ->whereIn('status', ['active', 'registered'])
            ->pluck('student_id')
            ->flip();

        return $rows->map(function ($row) use ($curriculaByCode, $alreadyEnrolledStudentIds, $semesterId, $careerId) {
            $errors     = [];
            $curriculaIds = [];
            $studentName  = null;

            // 1. Buscar estudiante por cédula
            $student = Student::whereHas('person', fn ($q) =>
                $q->where('identification', $row['cedula'])
            )->with('person')->first();

            if (! $student) {
                $errors[] = 'Estudiante no encontrado con esa cédula.';
            } else {
                $studentName = $student->person->full_name;

                // 2. Verificar que no esté ya matriculado
                if (isset($alreadyEnrolledStudentIds[$student->id])) {
                    $errors[] = 'Ya tiene una matrícula activa en este semestre y período.';
                }

                // 3. Resolver códigos de materias → curriculum IDs
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
                    $errors[] = 'Materias no encontradas: ' . implode(', ', $notFound);
                }

                // 4. Validar prerequisitos (solo si no hay otros errores)
                if (empty($errors) && ! empty($curriculaIds)) {
                    try {
                        $this->validatePrerequisites($student->id, $curriculaIds);
                    } catch (\DomainException $e) {
                        $errors[] = $e->getMessage();
                    }
                }
            }

            return [
                'cedula'           => $row['cedula'],
                'student_name'     => $studentName,
                'student_id'       => $student?->id,
                'career_id'        => $careerId,
                'codigos_materias' => $row['codigos_materias'],
                'curricula_ids'    => $curriculaIds,
                'can_enroll'       => empty($errors),
                'errors'           => $errors,
            ];
        })->values()->all();
    }

    /**
     * Procesa la matrícula masiva a partir de la previsualización ya confirmada.
     *
     * Recibe el array de filas validadas (solo las que can_enroll = true se procesan).
     * Devuelve un reporte con éxitos y omitidos.
     *
     * @param  array $rows            — filas parseadas del preview
     * @param  array $sharedData      — academic_period_id, semester_id, type, status, enrollment_date
     */
    public function bulkCreate(array $rows, array $sharedData): array
    {
        $enrolled = [];
        $skipped  = [];

        foreach ($rows as $row) {
            if (! $row['can_enroll']) {
                $skipped[] = [
                    'cedula'       => $row['cedula'],
                    'student_name' => $row['student_name'] ?? 'Desconocido',
                    'errors'       => $row['errors'],
                ];
                continue;
            }

            try {
                $this->create([
                    'student_id'         => $row['student_id'],
                    'career_id'          => $row['career_id'],
                    'semester_id'        => $sharedData['semester_id'],
                    'academic_period_id' => $sharedData['academic_period_id'],
                    'enrollment_date'    => $sharedData['enrollment_date'],
                    'type'               => $sharedData['type'],
                    'status'             => $sharedData['status'],
                    'curricula_ids'      => $row['curricula_ids'],
                ]);

                $enrolled[] = [
                    'cedula'       => $row['cedula'],
                    'student_name' => $row['student_name'],
                ];
            } catch (\DomainException $e) {
                $skipped[] = [
                    'cedula'       => $row['cedula'],
                    'student_name' => $row['student_name'] ?? 'Desconocido',
                    'errors'       => [$e->getMessage()],
                ];
            }
        }

        return [
            'enrolled_count' => count($enrolled),
            'skipped_count'  => count($skipped),
            'enrolled'       => $enrolled,
            'skipped'        => $skipped,
        ];
    }

    // =========================================================================
    // Materias disponibles para un estudiante
    // =========================================================================

    public function getAvailableSubjects(int $studentId, int $careerId, int $semesterId): array
    {
        $approvedSubjectIds = EnrollmentItem::whereHas('enrollment', fn ($q) =>
                $q->where('student_id', $studentId)
            )
            ->where('status', 'aprobado')
            ->with('curriculum:id,subject_id')
            ->get()
            ->pluck('curriculum.subject_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $inProgressCurriculaIds = EnrollmentItem::whereHas('enrollment', fn ($q) =>
                $q->where('student_id', $studentId)
                  ->whereIn('status', ['active', 'registered'])
            )
            ->where('status', 'en_curso')
            ->pluck('curricula_id')
            ->all();

        $currentCurricula = Curriculum::where('career_id', $careerId)
            ->where('semester_id', $semesterId)
            ->where('active', true)
            ->with('subject.prerequisiteSubjects')
            ->get();

        $carryoverCurricula = Curriculum::where('career_id', $careerId)
            ->where('semester_id', '!=', $semesterId)
            ->where('active', true)
            ->whereHas('semester', fn ($q) =>
                $q->where('number', '<', function ($sub) use ($semesterId) {
                    $sub->select('number')->from('semesters')->where('id', $semesterId);
                })
            )
            ->whereNotIn('id', $inProgressCurriculaIds)
            ->whereHas('subject', fn ($q) =>
                $q->whereNotIn('id', $approvedSubjectIds)
            )
            ->with('subject.prerequisiteSubjects', 'semester:id,name,number')
            ->get();

        return [
            'current'   => $this->mapCurricula($currentCurricula, $approvedSubjectIds, $inProgressCurriculaIds),
            'carryover' => $this->mapCurricula($carryoverCurricula, $approvedSubjectIds, $inProgressCurriculaIds),
        ];
    }

    // =========================================================================
    // Privados
    // =========================================================================

    private function validatePrerequisites(int $studentId, array $curriculaIds): void
    {
        $approvedSubjectIds = EnrollmentItem::whereHas('enrollment', fn ($q) =>
                $q->where('student_id', $studentId)
            )
            ->where('status', 'aprobado')
            ->with('curriculum:id,subject_id')
            ->get()
            ->pluck('curriculum.subject_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $curricula = Curriculum::whereIn('id', $curriculaIds)
            ->with('subject.prerequisiteSubjects')
            ->get();

        foreach ($curricula as $curriculum) {
            $missing = $curriculum->subject
                ->prerequisiteSubjects
                ->whereNotIn('id', $approvedSubjectIds);

            if ($missing->isNotEmpty()) {
                $names = $missing->pluck('name')->join(', ');
                throw new \DomainException(
                    "No puedes matricular \"{$curriculum->subject->name}\" porque te faltan aprobar: {$names}."
                );
            }
        }
    }

    private function mapCurricula(
        \Illuminate\Support\Collection $curricula,
        array $approvedSubjectIds,
        array $inProgressCurriculaIds
    ): array {
        return $curricula->map(function ($curriculum) use ($approvedSubjectIds, $inProgressCurriculaIds) {
            $missing = $curriculum->subject
                ->prerequisiteSubjects
                ->whereNotIn('id', $approvedSubjectIds)
                ->values();

            return [
                'curriculum_id'         => $curriculum->id,
                'subject_id'            => $curriculum->subject->id,
                'subject_name'          => $curriculum->subject->name,
                'subject_code'          => $curriculum->subject->code,
                'credits'               => $curriculum->subject->credits,
                'is_mandatory'          => $curriculum->is_mandatory,
                'semester'              => isset($curriculum->semester) ? [
                    'id'     => $curriculum->semester->id,
                    'name'   => $curriculum->semester->name,
                    'number' => $curriculum->semester->number,
                ] : null,
                'can_enroll'            => $missing->isEmpty(),
                'missing_prerequisites' => $missing->map(fn ($s) => [
                    'id'   => $s->id,
                    'name' => $s->name,
                    'code' => $s->code,
                ])->values()->all(),
                'already_enrolled'      => in_array($curriculum->id, $inProgressCurriculaIds),
            ];
        })->values()->all();
    }
}