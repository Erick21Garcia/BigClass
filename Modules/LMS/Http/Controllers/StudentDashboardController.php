<?php

namespace Modules\LMS\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Academic\Models\AcademicPeriod;
use Modules\Academic\Models\EnrollmentItem;
use Modules\LMS\Models\Unit;
use Modules\LMS\Models\VirtualCourse;
use Modules\LMS\Services\ProgressService;
use Modules\People\Models\Student;

class StudentDashboardController extends Controller
{
    public function __construct(
        private readonly ProgressService $progressService
    ) {}

    /**
     * Página inicial "Mis materias" — Inertia, carga todo de una vez
     * (Punto 3 del backlog: Opción B, esta parte es la mitad Inertia).
     */
    public function index(Request $request): Response
    {
        $student = $this->resolveStudent($request);
        $activePeriod = AcademicPeriod::active()->first();

        $items = EnrollmentItem::whereHas('enrollment', function ($q) use ($student, $activePeriod) {
                $q->where('student_id', $student->id);

                if ($activePeriod) {
                    $q->where('academic_period_id', $activePeriod->id);
                }
            })
            ->where('active', true)
            ->with(['section.virtualCourse', 'section.teacher.person', 'curriculum.subject'])
            ->get();

        $subjects = $items->map(function (EnrollmentItem $item) use ($student) {
            $section = $item->section;
            $virtualCourse = $section?->virtualCourse;

            // Punto 1.a del backlog: sin VirtualCourse o no publicado =
            // materia deshabilitada, no oculta.
            $enabled = (bool) ($virtualCourse && $virtualCourse->is_published);

            return [
                'section_id'        => $section->id,
                'virtual_course_id' => $enabled ? $virtualCourse->id : null,
                'subject_name'      => $item->curriculum->subject->name,
                'teacher_name'      => $section->teacher?->person?->full_name,
                'enabled'           => $enabled,
                'disabled_message'  => $enabled ? null : 'El docente aún no ha activado esta materia.',
                // Punto 1.b: progreso calculado aquí mismo, una vez por materia.
                'progress'          => $enabled
                    ? $this->progressService->courseProgress($virtualCourse, $student)
                    : null,
            ];
        })->values();

        return Inertia::render('lms/student/Index', [
            'subjects' => $subjects,
        ]);
    }

    /**
     * Detalle de una materia + sus Unidades con estado por ítem — JSON,
     * consumido al hacer clic en el sidebar o abrir una Unidad (Punto 3
     * del backlog: Opción B, esta parte es la mitad sin recargar).
     */
    public function show(Request $request, VirtualCourse $virtualCourse): JsonResponse
    {
        $student = $this->resolveStudent($request);

        abort_unless(
            EnrollmentItem::where('section_id', $virtualCourse->section_id)
                ->where('active', true)
                ->whereHas('enrollment', fn ($q) => $q->where('student_id', $student->id))
                ->exists(),
            403,
            'No estás matriculado en esta materia.'
        );

        abort_unless($virtualCourse->is_published, 404);

        $units = $virtualCourse->units()->active()->get();

        return response()->json([
            'virtual_course_id' => $virtualCourse->id,
            'course_progress'   => $this->progressService->courseProgress($virtualCourse, $student),
            'units'             => $units->map(fn (Unit $unit) => [
                'id'       => $unit->id,
                'name'     => $unit->name,
                'progress' => $this->progressService->unitProgress($unit, $student),
                'items'    => $this->progressService->unitItemsStatus($unit, $student),
            ]),
        ]);
    }

    /**
     * Detalle de una tarea + formulario de entrega — pieza #3 pendiente.
     */
    public function assignment(Request $request, \Modules\LMS\Models\Assignment $assignment): Response
    {
        $student = $this->resolveStudent($request);

        $section = $assignment->unit->virtualCourse->section;
        abort_unless(
            EnrollmentItem::where('section_id', $section->id)
                ->where('active', true)
                ->whereHas('enrollment', fn ($q) => $q->where('student_id', $student->id))
                ->exists(),
            403
        );

        $submission = \Modules\LMS\Models\AssignmentSubmission::where('assignment_id', $assignment->id)
            ->where('student_id', $student->id)
            ->with('files')
            ->first();

        return Inertia::render('lms/student/Assignment', [
            'virtual_course_id' => $assignment->unit->virtualCourse->id,
            'assignment' => [
                'id'          => $assignment->id,
                'title'       => $assignment->title,
                'description' => $assignment->description,
                'due_date'    => $assignment->due_date,
            ],
            'submission' => $submission ? [
                'submitted_at' => $submission->submitted_at,
                'is_late'      => $submission->is_late,
                'grade'        => $submission->grade,
                'feedback'     => $submission->feedback,
                'files'        => $submission->files->map(fn ($f) => ['id' => $f->id, 'original_name' => $f->original_name]),
            ] : null,
        ]);
    }

    /**
     * Vista previa de un cuestionario (intentos usados, historial) antes
     * de empezar a responder — pieza #4 pendiente, parte 1.
     */
    public function quiz(Request $request, \Modules\LMS\Models\Quiz $quiz): Response
    {
        $student = $this->resolveStudent($request);

        $section = $quiz->unit->virtualCourse->section;
        abort_unless(
            EnrollmentItem::where('section_id', $section->id)
                ->where('active', true)
                ->whereHas('enrollment', fn ($q) => $q->where('student_id', $student->id))
                ->exists(),
            403
        );

        $attempts = \Modules\LMS\Models\QuizAttempt::where('quiz_id', $quiz->id)
            ->where('student_id', $student->id)
            ->orderByDesc('attempt_number')
            ->get();

        $inProgress = $attempts->firstWhere('status', 'in_progress');

        return Inertia::render('lms/student/Quiz', [
            'virtual_course_id' => $quiz->unit->virtualCourse->id,
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'max_attempts' => $quiz->max_attempts,
            ],
            'attempts_used' => $attempts->count(),
            'in_progress_attempt_id' => $inProgress?->id,
            'attempts' => $attempts->map(fn ($a) => [
                'attempt_number' => $a->attempt_number,
                'status'         => $a->status,
                'final_score'    => $a->final_score,
            ]),
        ]);
    }

    private function resolveStudent(Request $request): Student
    {
        return Student::whereHas('person', fn ($q) => $q->where('user_id', $request->user()->id))
            ->firstOrFail();
    }
}