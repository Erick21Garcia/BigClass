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