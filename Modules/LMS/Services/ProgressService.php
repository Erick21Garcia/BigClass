<?php

namespace Modules\LMS\Services;

use Modules\LMS\Models\ResourceView;
use Modules\LMS\Models\Unit;
use Modules\LMS\Models\VirtualCourse;
use Modules\People\Models\Student;

class ProgressService
{
    public function markResourceAsViewed(\Modules\LMS\Models\Resource $resource, Student $student): ResourceView
    {
        return ResourceView::updateOrCreate(
            ['resource_id' => $resource->id, 'student_id' => $student->id],
            ['viewed_at' => now()]
        );
    }

    /**
     * % de avance de UNA unidad para un estudiante. "Completado" =
     * recurso marcado como leído manualmente, tarea con entrega hecha
     * (submitted_at no nulo, sin importar si ya está calificada), o
     * cuestionario con al menos un intento entregado.
     */
    public function unitProgress(Unit $unit, Student $student): float
    {
        $unit->loadMissing(['resources', 'assignments', 'quizzes']);

        $totalItems = $unit->resources->count()
            + $unit->assignments->count()
            + $unit->quizzes->count();

        if ($totalItems === 0) {
            return 0.0;
        }

        $viewedResourceIds = ResourceView::where('student_id', $student->id)
            ->whereIn('resource_id', $unit->resources->pluck('id'))
            ->pluck('resource_id');

        $submittedAssignments = \Modules\LMS\Models\AssignmentSubmission::where('student_id', $student->id)
            ->whereIn('assignment_id', $unit->assignments->pluck('id'))
            ->whereNotNull('submitted_at')
            ->count();

        $attemptedQuizzes = \Modules\LMS\Models\QuizAttempt::where('student_id', $student->id)
            ->whereIn('quiz_id', $unit->quizzes->pluck('id'))
            ->whereNotNull('submitted_at')
            ->distinct('quiz_id')
            ->count('quiz_id');

        $completedItems = $viewedResourceIds->count() + $submittedAssignments + $attemptedQuizzes;

        return round(($completedItems / $totalItems) * 100, 1);
    }

    /**
     * Detalle por ítem de una Unidad para el acordeón del frontend.
     * Estados: recurso 'read'|'pending'; tarea 'submitted'|'pending'
     * (la nota NO se expone aquí — Punto 2 del backlog); cuestionario
     * 'graded'|'in_review'|'pending'.
     */
    public function unitItemsStatus(Unit $unit, Student $student): array
    {
        $unit->loadMissing(['resources', 'assignments', 'quizzes']);

        $viewedResourceIds = ResourceView::where('student_id', $student->id)
            ->whereIn('resource_id', $unit->resources->pluck('id'))
            ->pluck('resource_id');

        $submissionsByAssignment = \Modules\LMS\Models\AssignmentSubmission::where('student_id', $student->id)
            ->whereIn('assignment_id', $unit->assignments->pluck('id'))
            ->whereNotNull('submitted_at')
            ->pluck('assignment_id');

        $attemptsByQuiz = \Modules\LMS\Models\QuizAttempt::where('student_id', $student->id)
            ->whereIn('quiz_id', $unit->quizzes->pluck('id'))
            ->get()
            ->groupBy('quiz_id');

        $items = [];

        foreach ($unit->resources as $resource) {
            $items[] = [
                'type'         => 'resource',
                'id'           => $resource->id,
                'title'        => $resource->title,
                'status'       => $viewedResourceIds->contains($resource->id) ? 'read' : 'pending',
                'download_url' => $resource->downloadUrl(),
            ];
        }

        foreach ($unit->assignments as $assignment) {
            $items[] = [
                'type'   => 'assignment',
                'id'     => $assignment->id,
                'title'  => $assignment->title,
                'status' => $submissionsByAssignment->contains($assignment->id) ? 'submitted' : 'pending',
            ];
        }

        foreach ($unit->quizzes as $quiz) {
            $attempts = $attemptsByQuiz->get($quiz->id, collect());

            $status = match (true) {
                $attempts->contains(fn ($a) => $a->status === 'graded')         => 'graded',
                $attempts->contains(fn ($a) => $a->status === 'pending_review') => 'in_review',
                default                                                         => 'pending',
            };

            $items[] = [
                'type'   => 'quiz',
                'id'     => $quiz->id,
                'title'  => $quiz->title,
                'status' => $status,
            ];
        }

        return $items;
    }

    /**
     * Contador combinado (Punto 1 del backlog docente): tareas entregadas
     * sin calificar + cuestionarios en pending_review, sumados en un solo
     * número, para toda la materia.
     */
    public function pendingGradingCount(VirtualCourse $course): int
    {
        $unitIds = $course->units()->pluck('id');

        $pendingAssignments = \Modules\LMS\Models\AssignmentSubmission::whereIn(
                'assignment_id',
                \Modules\LMS\Models\Assignment::whereIn('unit_id', $unitIds)->pluck('id')
            )
            ->whereNotNull('submitted_at')
            ->whereNull('grade')
            ->count();

        $pendingQuizzes = \Modules\LMS\Models\QuizAttempt::whereIn(
                'quiz_id',
                \Modules\LMS\Models\Quiz::whereIn('unit_id', $unitIds)->pluck('id')
            )
            ->where('status', 'pending_review')
            ->count();

        return $pendingAssignments + $pendingQuizzes;
    }

    /**
     * % de avance de TODO el curso (promedio simple de sus unidades
     * activas). Unidades sin contenido no cuentan (no distorsionan el
     * promedio hacia abajo).
     */
    public function courseProgress(VirtualCourse $course, Student $student): float
    {
        $units = $course->units()->active()->get();

        $unitsWithContent = $units->filter(
            fn (Unit $unit) => $unit->resources()->count() + $unit->assignments()->count() + $unit->quizzes()->count() > 0
        );

        if ($unitsWithContent->isEmpty()) {
            return 0.0;
        }

        $average = $unitsWithContent
            ->map(fn (Unit $unit) => $this->unitProgress($unit, $student))
            ->avg();

        return round($average, 1);
    }
}