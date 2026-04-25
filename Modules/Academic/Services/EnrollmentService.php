<?php

namespace Modules\Academic\Services;

use Modules\Academic\Models\Enrollment;
use Modules\Academic\Models\EnrollmentItem;
use Modules\Institucion\Models\Curriculum;

class EnrollmentService
{
    /**
     * Crea la matrícula y sus items, validando prerequisitos por cada materia.
     *
     * @param  array{
     *   student_id: int,
     *   career_id: int,
     *   semester_id: int,
     *   academic_period_id: int,
     *   enrollment_date: string,
     *   type: string,
     *   status: string,
     *   curricula_ids: int[],
     * } $data
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
     * Los items se manejan por separado con syncItems().
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
        if ($enrollment->is_active) {
            throw new \DomainException('No se puede eliminar una matrícula activa.');
        }

        $enrollment->delete();
    }

    /**
     * Devuelve las materias que el estudiante puede matricular en un semestre,
     * agrupadas en dos bloques:
     *   - current:  materias del semestre indicado
     *   - carryover: materias de semestres anteriores que reprobó o no cursó
     *
     * Cada materia incluye:
     *   - can_enroll:      bool  — si cumple todos los prerequisitos
     *   - missing:         array — prerequisitos que le faltan aprobar
     *   - already_enrolled bool  — si ya está cursando esta materia actualmente
     */
    public function getAvailableSubjects(int $studentId, int $careerId, int $semesterId): array
    {
        // Todas las materias aprobadas por el estudiante (subject_id[])
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

        // Materias que el estudiante tiene actualmente en curso
        $inProgressCurriculaIds = EnrollmentItem::whereHas('enrollment', fn ($q) =>
                $q->where('student_id', $studentId)
                  ->whereIn('status', ['active', 'registered'])
            )
            ->where('status', 'en_curso')
            ->pluck('curricula_id')
            ->all();

        // Materias del semestre actual en la carrera
        $currentCurricula = Curriculum::where('career_id', $careerId)
            ->where('semester_id', $semesterId)
            ->where('active', true)
            ->with('subject.prerequisiteSubjects')
            ->get();

        // Materias de semestres anteriores que el estudiante nunca aprobó
        // y tampoco está cursando actualmente (arrastre)
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

    // -------------------------------------------------------------------------
    // Privados
    // -------------------------------------------------------------------------

    /**
     * Valida que el estudiante haya aprobado todos los prerequisitos
     * de cada materia que quiere matricular.
     *
     * @throws \DomainException con el detalle de qué falta
     */
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

    /**
     * Mapea una colección de Curriculum al formato que consume el frontend.
     */
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
                'curriculum_id'    => $curriculum->id,
                'subject_id'       => $curriculum->subject->id,
                'subject_name'     => $curriculum->subject->name,
                'subject_code'     => $curriculum->subject->code,
                'credits'          => $curriculum->subject->credits,
                'is_mandatory'     => $curriculum->is_mandatory,
                'semester'         => isset($curriculum->semester) ? [
                    'id'     => $curriculum->semester->id,
                    'name'   => $curriculum->semester->name,
                    'number' => $curriculum->semester->number,
                ] : null,
                'can_enroll'       => $missing->isEmpty(),
                'missing_prerequisites' => $missing->map(fn ($s) => [
                    'id'   => $s->id,
                    'name' => $s->name,
                    'code' => $s->code,
                ])->values()->all(),
                'already_enrolled' => in_array($curriculum->id, $inProgressCurriculaIds),
            ];
        })->values()->all();
    }
}