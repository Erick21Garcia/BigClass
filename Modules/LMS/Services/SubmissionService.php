<?php

namespace Modules\LMS\Services;

use Illuminate\Support\Facades\Storage;
use Modules\LMS\Models\Assignment;
use Modules\LMS\Models\AssignmentSubmission;
use Modules\People\Models\Student;

class SubmissionService
{
    private const DISK = 'lms_materials';

    public function __construct(
        private readonly GradeSyncService $gradeSyncService,
    ) {}

    /**
     * Crea o actualiza la entrega de un estudiante para una tarea.
     * $files es un array de UploadedFile (múltiples archivos por entrega,
     * según lo definido en el Punto 5).
     */
    public function submit(Assignment $assignment, Student $student, array $files): AssignmentSubmission
    {
        $submission = AssignmentSubmission::updateOrCreate(
            [
                'assignment_id' => $assignment->id,
                'student_id'    => $student->id,
            ],
            [
                'submitted_at' => now(),
                'is_late'      => now()->greaterThan($assignment->due_date),
            ]
        );

        // Si ya tenía archivos de una entrega anterior, se reemplazan
        // (no se acumulan versiones en esta primera versión).
        foreach ($submission->files as $existingFile) {
            Storage::disk(self::DISK)->delete($existingFile->file_path);
            $existingFile->delete();
        }

        foreach ($files as $file) {
            $path = $file->store("submissions/{$submission->id}", self::DISK);

            $submission->files()->create([
                'file_path'     => $path,
                'original_name' => $file->getClientOriginalName(),
            ]);
        }

        return $submission->fresh('files');
    }

    /**
     * Califica una entrega con nota + retroalimentación (Punto 5) y
     * sincroniza automáticamente con Grade del ISI (Punto 2, Opción A).
     *
     * Lanza RuntimeException si el periodo académico ya está cerrado
     * (Grade.locked = true) — el controller debe capturarla y mostrar
     * el mensaje al docente.
     */
    public function grade(AssignmentSubmission $submission, float $grade, ?string $feedback, int $gradedByUserId): AssignmentSubmission
    {
        $submission->update([
            'grade'      => $grade,
            'feedback'   => $feedback,
            'graded_at'  => now(),
            'graded_by'  => $gradedByUserId,
        ]);

        $this->gradeSyncService->syncFromAssignmentSubmission($submission);

        return $submission;
    }
}