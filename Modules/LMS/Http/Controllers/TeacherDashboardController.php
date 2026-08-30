<?php

namespace Modules\LMS\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Academic\Models\AcademicPeriod;
use Modules\Academic\Models\EnrollmentItem;
use Modules\Academic\Models\Section;
use Modules\LMS\Models\Assignment;
use Modules\LMS\Models\AssignmentSubmission;
use Modules\LMS\Models\Quiz;
use Modules\LMS\Models\QuizAttempt;
use Modules\LMS\Models\VirtualCourse;
use Modules\LMS\Services\ProgressService;
use Modules\People\Models\Teacher;

class TeacherDashboardController extends Controller
{
    public function __construct(
        private readonly ProgressService $progressService
    ) {}

    /**
     * "Mis materias" del docente — Punto 1 del backlog docente.
     */
    public function index(Request $request): Response
    {
        $teacher = $this->resolveTeacher($request);
        $activePeriod = AcademicPeriod::active()->first();

        $sections = Section::where('teacher_id', $teacher->id)
            ->when($activePeriod, fn ($q) => $q->where('academic_period_id', $activePeriod->id))
            ->where('active', true)
            ->with(['virtualCourse', 'curriculum.subject'])
            ->get();

        $subjects = $sections->map(function (Section $section) {
            $virtualCourse = $section->virtualCourse;
            $studentsCount = EnrollmentItem::where('section_id', $section->id)
                ->where('active', true)
                ->count();

            return [
                'section_id'        => $section->id,
                'virtual_course_id' => $virtualCourse?->id,
                'subject_name'      => $section->curriculum->subject->name,
                'has_virtual_course'=> (bool) $virtualCourse,
                'is_published'      => (bool) $virtualCourse?->is_published,
                'students_count'    => $studentsCount,
                // Punto 1: contador combinado, solo si ya existe VirtualCourse.
                'pending_count'     => $virtualCourse
                    ? $this->progressService->pendingGradingCount($virtualCourse)
                    : 0,
            ];
        })->values();

        return Inertia::render('lms/teacher/Index', [
            'subjects' => $subjects,
        ]);
    }

    /**
     * Matriz de calificación — Punto 3 del backlog docente. Filas =
     * estudiantes, columnas = cada tarea/cuestionario de la materia,
     * con la nota real (no solo el estado) donde ya esté calificada.
     */
    public function gradebook(Request $request, VirtualCourse $virtualCourse): Response
    {
        $teacher = $this->resolveTeacher($request);
        $section = $virtualCourse->section;

        abort_unless($section->teacher_id === $teacher->id, 403);

        $unitIds = $virtualCourse->units()->pluck('id');

        $assignments = Assignment::whereIn('unit_id', $unitIds)->where('active', true)->get();
        $quizzes = Quiz::whereIn('unit_id', $unitIds)->where('active', true)->get();

        $students = EnrollmentItem::where('section_id', $section->id)
            ->where('active', true)
            ->with('enrollment.student.person')
            ->get()
            ->map(fn (EnrollmentItem $item) => $item->enrollment->student)
            ->unique('id')
            ->values();

        $submissionsByAssignment = AssignmentSubmission::whereIn('assignment_id', $assignments->pluck('id'))
            ->get()
            ->groupBy(['assignment_id', 'student_id']);

        $attemptsByQuiz = QuizAttempt::whereIn('quiz_id', $quizzes->pluck('id'))
            ->get()
            ->groupBy(['quiz_id', 'student_id']);

        $rows = $students->map(function ($student) use ($assignments, $quizzes, $submissionsByAssignment, $attemptsByQuiz) {
            $cells = [];

            foreach ($assignments as $assignment) {
                $submission = $submissionsByAssignment
                    ->get($assignment->id, collect())
                    ->get($student->id, collect())
                    ->first();

                $cells[] = [
                    'type'   => 'assignment',
                    'id'     => $assignment->id,
                    'grade'  => $submission?->grade,
                    'submission_id' => $submission?->id,
                    'status' => match (true) {
                        !$submission || !$submission->submitted_at => 'pending',
                        $submission->grade !== null                => 'graded',
                        default                                     => 'submitted',
                    },
                ];
            }

            foreach ($quizzes as $quiz) {
                $attempts = $attemptsByQuiz->get($quiz->id, collect())->get($student->id, collect());
                $bestGraded = $attempts->where('status', 'graded')->max('final_score');
                $pendingAttempt = $attempts->firstWhere('status', 'pending_review');

                $cells[] = [
                    'type'   => 'quiz',
                    'id'     => $quiz->id,
                    'grade'  => $bestGraded,
                    'attempt_id' => $pendingAttempt?->id,
                    'status' => match (true) {
                        $bestGraded !== null                                            => 'graded',
                        $pendingAttempt                                                   => 'in_review',
                        $attempts->isNotEmpty()                                           => 'submitted',
                        default                                                            => 'pending',
                    },
                ];
            }

            return [
                'student_id'   => $student->id,
                'student_name' => $student->person->full_name,
                'cells'        => $cells,
            ];
        });

        return Inertia::render('lms/teacher/Gradebook', [
            'virtual_course_id' => $virtualCourse->id,
            'subject_name'      => $section->curriculum->subject->name,
            'columns'           => [
                ...$assignments->map(fn ($a) => ['type' => 'assignment', 'id' => $a->id, 'title' => $a->title]),
                ...$quizzes->map(fn ($q) => ['type' => 'quiz', 'id' => $q->id, 'title' => $q->title]),
            ],
            'rows' => $rows,
        ]);
    }

    /**
     * "Gestionar materia" — Punto 2 del backlog docente. Acordeón editable
     * con Unidades + sus ítems, para agregar materiales/tareas/cuestionarios.
     */
    public function manage(Request $request, VirtualCourse $virtualCourse): Response
    {
        $teacher = $this->resolveTeacher($request);
        $section = $virtualCourse->section;

        abort_unless($section->teacher_id === $teacher->id, 403);

        $units = $virtualCourse->units()->active()->with(['resources', 'assignments', 'quizzes'])->get();

        $evaluationParameters = \Modules\Academic\Models\EvaluationParameter::where('academic_period_id', $section->academic_period_id)
            ->where('active', true)
            ->get(['id', 'name']);

        return Inertia::render('lms/teacher/Manage', [
            'virtual_course_id' => $virtualCourse->id,
            'subject_name'      => $section->curriculum->subject->name,
            'is_published'      => $virtualCourse->is_published,
            'evaluation_parameters' => $evaluationParameters,
            'units' => $units->map(fn ($unit) => [
                'id'   => $unit->id,
                'name' => $unit->name,
                'resources'   => $unit->resources->map(fn ($r) => ['id' => $r->id, 'title' => $r->title]),
                'assignments' => $unit->assignments->map(fn ($a) => ['id' => $a->id, 'title' => $a->title]),
                'quizzes'     => $unit->quizzes->map(fn ($q) => ['id' => $q->id, 'title' => $q->title]),
            ]),
        ]);
    }

    private function resolveTeacher(Request $request): Teacher
    {
        return Teacher::whereHas('person', fn ($q) => $q->where('user_id', $request->user()->id))
            ->firstOrFail();
    }
}