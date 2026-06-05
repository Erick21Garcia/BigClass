<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Academic\Exports\BulkEnrollmentTemplateExport;
use Modules\Academic\Http\Requests\BulkEnrollmentRequest;
use Modules\Academic\Http\Requests\StoreEnrollmentRequest;
use Modules\Academic\Http\Requests\UpdateEnrollmentRequest;
use Modules\Academic\Models\AcademicPeriod;
use Modules\Academic\Models\Enrollment;
use Modules\Academic\Services\EnrollmentService;
use Modules\Institucion\Models\Semester;
use Modules\People\Models\Student;

class EnrollmentController extends Controller
{
    public function __construct(
        private EnrollmentService $enrollmentService
    ) {}

    // =========================================================================
    // CRUD individual (sin cambios)
    // =========================================================================

    public function index()
    {
        $enrollments = Enrollment::query()
            ->with(['student.person', 'career', 'semester', 'academicPeriod'])
            ->orderBy('enrollment_date', 'desc')
            ->get()
            ->map(fn ($enrollment) => [
                'id'              => $enrollment->id,
                'enrollment_date' => $enrollment->enrollment_date?->format('Y-m-d'),
                'type'            => $enrollment->type,
                'status'          => $enrollment->status,
                'active'          => $enrollment->active,
                'student'         => [
                    'id'        => $enrollment->student->id,
                    'full_name' => $enrollment->student->person->full_name,
                ],
                'career'          => [
                    'id'   => $enrollment->career->id,
                    'name' => $enrollment->career->name,
                ],
                'semester'        => [
                    'id'   => $enrollment->semester->id,
                    'name' => $enrollment->semester->name,
                ],
                'academic_period' => [
                    'id'   => $enrollment->academicPeriod->id,
                    'name' => $enrollment->academicPeriod->name,
                ],
            ]);

        return Inertia::render('enrollments/Index', compact('enrollments'));
    }

    public function create()
    {
        $semesterId = request()->integer('semester_id');
        $studentId  = request()->integer('student_id');

        abort_if(! $semesterId, 400, 'Se requiere un semestre para matricular.');

        $semester = Semester::with('career.faculty.institution')->findOrFail($semesterId);

        $availableSubjects = $studentId
            ? $this->enrollmentService->getAvailableSubjects($studentId, $semester->career_id, $semesterId)
            : ['current' => [], 'carryover' => []];

        $enrolledStudentIds = Enrollment::where('semester_id', $semesterId)
            ->where('career_id', $semester->career_id)
            ->whereIn('status', ['active', 'registered'])
            ->pluck('student_id');

        $students = Student::with('person')
            ->where('active', true)
            ->whereNotIn('id', $enrolledStudentIds)
            ->get()
            ->map(fn ($s) => [
                'id'                => $s->id,
                'enrollment_number' => $s->enrollment_number,
                'full_name'         => $s->person->full_name,
            ]);

        $academicPeriods = AcademicPeriod::orderBy('start_date', 'desc')
            ->get(['id', 'name']);

        return Inertia::render('enrollments/Create', [
            'semester' => [
                'id'       => $semester->id,
                'name'     => $semester->name,
                'number'   => $semester->number,
                'career_id' => $semester->career_id,
                'career'   => [
                    'id'   => $semester->career->id,
                    'name' => $semester->career->name,
                    'faculty' => [
                        'id'   => $semester->career->faculty->id,
                        'name' => $semester->career->faculty->name,
                        'institution' => [
                            'id'   => $semester->career->faculty->institution->id,
                            'name' => $semester->career->faculty->institution->name,
                        ],
                    ],
                ],
            ],
            'students'          => $students,
            'academicPeriods'   => $academicPeriods,
            'selectedStudentId' => $studentId ?: null,
            'availableSubjects' => $availableSubjects,
        ]);
    }

    public function show(Enrollment $enrollment)
    {
        $enrollment->load(['student.person', 'career', 'semester', 'academicPeriod', 'items.curriculum.subject']);

        return Inertia::render('enrollments/Show', [
            'enrollment' => [
                'id'              => $enrollment->id,
                'enrollment_date' => $enrollment->enrollment_date?->format('Y-m-d'),
                'type'            => $enrollment->type,
                'status'          => $enrollment->status,
                'active'          => $enrollment->active,
                'student'         => [
                    'id'        => $enrollment->student->id,
                    'full_name' => $enrollment->student->person->full_name,
                ],
                'career'          => [
                    'id'   => $enrollment->career->id,
                    'name' => $enrollment->career->name,
                ],
                'semester'        => [
                    'id'   => $enrollment->semester->id,
                    'name' => $enrollment->semester->name,
                ],
                'academic_period' => [
                    'id'   => $enrollment->academicPeriod->id,
                    'name' => $enrollment->academicPeriod->name,
                ],
                'items' => $enrollment->items->map(fn ($item) => [
                    'id'          => $item->id,
                    'status'      => $item->status,
                    'final_grade' => $item->final_grade,
                    'active'      => $item->active,
                    'subject'     => [
                        'id'      => $item->curriculum->subject->id,
                        'name'    => $item->curriculum->subject->name,
                        'code'    => $item->curriculum->subject->code,
                        'credits' => $item->curriculum->subject->credits,
                    ],
                ]),
            ],
        ]);
    }

    public function store(StoreEnrollmentRequest $request)
    {
        try {
            $this->enrollmentService->create($request->validated());
        } catch (\DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('semesters.show', $request->validated()['semester_id'])
            ->with('success', 'Matrícula creada exitosamente.');
    }

    public function edit(Enrollment $enrollment)
    {
        $enrollment->load(['student.person', 'academicPeriod']);

        $academicPeriods = AcademicPeriod::orderBy('start_date', 'desc')
            ->get(['id', 'name']);

        return Inertia::render('enrollments/Edit', [
            'enrollment' => [
                'id'                 => $enrollment->id,
                'student_id'         => $enrollment->student_id,
                'full_name'          => $enrollment->student->person->full_name,
                'career_id'          => $enrollment->career_id,
                'semester_id'        => $enrollment->semester_id,
                'academic_period_id' => $enrollment->academic_period_id,
                'enrollment_date'    => $enrollment->enrollment_date?->format('Y-m-d'),
                'type'               => $enrollment->type,
                'status'             => $enrollment->status,
            ],
            'academicPeriods' => $academicPeriods,
        ]);
    }

    public function update(UpdateEnrollmentRequest $request, Enrollment $enrollment)
    {
        $this->enrollmentService->update($enrollment, $request->validated());

        return redirect()
            ->route('semesters.show', $enrollment->semester_id)
            ->with('success', 'Matrícula actualizada exitosamente.');
    }

    public function destroy(Enrollment $enrollment)
    {
        $semesterId = $enrollment->semester_id;

        try {
            $this->enrollmentService->delete($enrollment);
        } catch (\DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('semesters.show', $semesterId)
            ->with('success', 'Matrícula eliminada exitosamente.');
    }

    public function grades(Enrollment $enrollment)
    {
        $enrollment->load([
            'academicPeriod',
            'student.person',
            'items.curriculum.subject',
            'items.grades.evaluationParameter',
        ]);

        $academicPeriodId = $enrollment->academic_period_id;

        $items = $enrollment->items->where('active', true)->map(function ($item) use ($academicPeriodId) {
            $parameters = \Modules\Academic\Models\EvaluationParameter::where('academic_period_id', $academicPeriodId)
                ->where('curriculum_id', $item->curricula_id)
                ->where('active', true)
                ->get();

            if ($parameters->isEmpty()) {
                $parameters = \Modules\Academic\Models\EvaluationParameter::where('academic_period_id', $academicPeriodId)
                    ->whereNull('curriculum_id')
                    ->where('active', true)
                    ->get();
            }

            $parameters    = $parameters->sortBy('created_at')->values();
            $gradesByParam = $item->grades->keyBy('evaluation_parameter_id');

            return [
                'enrollment_item_id' => $item->id,
                'status'             => $item->status,
                'final_grade'        => $item->final_grade,
                'subject'            => [
                    'id'   => $item->curriculum->subject->id,
                    'name' => $item->curriculum->subject->name,
                    'code' => $item->curriculum->subject->code,
                ],
                'parameters' => $parameters->map(fn ($p) => [
                    'id'         => $p->id,
                    'name'       => $p->name,
                    'percentage' => $p->percentage,
                    'is_final'   => $p->is_final,
                ])->values()->all(),
                'grades' => $parameters->map(fn ($p) => [
                    'evaluation_parameter_id' => $p->id,
                    'grade_id'                => $gradesByParam[$p->id]->id ?? null,
                    'score'                   => isset($gradesByParam[$p->id]) ? (float) $gradesByParam[$p->id]->score : null,
                ])->values()->all(),
            ];
        })->values()->all();

        return response()->json([
            'enrollment_id'   => $enrollment->id,
            'student'         => $enrollment->student->person->full_name ?? '',
            'academic_period' => $enrollment->academicPeriod->name,
            'items'           => $items,
        ]);
    }

    // =========================================================================
    // Matrícula masiva
    // =========================================================================

    /**
     * Descarga la plantilla Excel para matrícula masiva.
     */
    public function bulkTemplate()
    {
        return Excel::download(
            new BulkEnrollmentTemplateExport(),
            'plantilla-matricula-masiva.xlsx',
        );
    }

    /**
     * Recibe el Excel y devuelve la previsualización (sin guardar nada).
     * El frontend muestra la tabla y el usuario confirma.
     */
    public function bulkPreview(BulkEnrollmentRequest $request)
    {
        $semester = Semester::findOrFail($request->integer('semester_id'));

        $preview = $this->enrollmentService->bulkPreview(
            file:             $request->file('file'),
            semesterId:       $semester->id,
            careerId:         $semester->career_id,
            academicPeriodId: $request->integer('academic_period_id'),
        );

        return response()->json([
            'preview'        => $preview,
            'total'          => count($preview),
            'can_enroll'     => collect($preview)->where('can_enroll', true)->count(),
            'will_skip'      => collect($preview)->where('can_enroll', false)->count(),
        ]);
    }

    /**
     * Recibe la previsualización confirmada y ejecuta las matrículas.
     */
    public function bulkStore(BulkEnrollmentRequest $request)
    {
        $semester = Semester::findOrFail($request->integer('semester_id'));

        // Re-genera el preview desde el archivo para no confiar en datos del cliente
        $preview = $this->enrollmentService->bulkPreview(
            file:             $request->file('file'),
            semesterId:       $semester->id,
            careerId:         $semester->career_id,
            academicPeriodId: $request->integer('academic_period_id'),
        );

        $report = $this->enrollmentService->bulkCreate(
            rows:       $preview,
            sharedData: [
                'semester_id'        => $semester->id,
                'academic_period_id' => $request->integer('academic_period_id'),
                'type'               => $request->input('type'),
                'status'             => $request->input('status'),
                'enrollment_date'    => $request->input('enrollment_date'),
            ],
        );

        return response()->json([
            'success'        => true,
            'enrolled_count' => $report['enrolled_count'],
            'skipped_count'  => $report['skipped_count'],
            'enrolled'       => $report['enrolled'],
            'skipped'        => $report['skipped'],
        ]);
    }
}