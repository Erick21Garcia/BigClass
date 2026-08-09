<?php

namespace Modules\LMS\Services;

use Modules\Academic\Models\EnrollmentItem;
use Modules\Academic\Models\Grade;
use Modules\LMS\Models\AssignmentSubmission;

class GradeSyncService
{
    /**
     * Integración Punto 2 (Opción A): la nota calificada en el LMS se
     * escribe directamente en Grade del ISI. No hay doble captura.
     *
     * Ruta para encontrar el EnrollmentItem correcto:
     * AssignmentSubmission -> Assignment -> Unit -> VirtualCourse -> Section
     * (que ya trae section_id) + student_id de la propia entrega.
     * EnrollmentItem tiene section_id directo, así que no hace falta pasar
     * por academic_period_id ni curricula_id manualmente.
     */
    public function syncFromAssignmentSubmission(AssignmentSubmission $submission): Grade
    {
        $assignment = $submission->assignment;
        $section = $assignment->unit->virtualCourse->section;

        $enrollmentItem = EnrollmentItem::where('section_id', $section->id)
            ->whereHas('enrollment', fn ($q) => $q->where('student_id', $submission->student_id))
            ->first();

        if (! $enrollmentItem) {
            throw new \RuntimeException(
                "No se encontró matrícula del estudiante #{$submission->student_id} en la sección #{$section->id}. " .
                'No se puede sincronizar la nota con el ISI.'
            );
        }

        $grade = Grade::firstOrNew([
            'enrollment_item_id'      => $enrollmentItem->id,
            'evaluation_parameter_id' => $assignment->evaluation_parameter_id,
        ]);

        // Respeta el bloqueo de ClosePeriodService: si el periodo ya cerró
        // y esta nota quedó locked, no se permite sobreescribir desde el LMS.
        if ($grade->exists && $grade->locked) {
            throw new \RuntimeException(
                'No se puede actualizar la nota: el periodo académico ya está ' .
                'cerrado y esta calificación está bloqueada.'
            );
        }

        $grade->score = $submission->grade;
        $grade->observations = $submission->feedback;
        $grade->active = true;
        $grade->save();

        return $grade;
    }
}