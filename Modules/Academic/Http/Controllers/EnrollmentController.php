<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
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
                'active'       => $enrollment->active,
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
                'id'     => $semester->id,
                'name'   => $semester->name,
                'number' => $semester->number,
                'career' => [
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
                'active'       => $enrollment->active,
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

            $parameters = $parameters->sortBy('created_at')->values();
            $gradesByParam = $item->grades->keyBy('evaluation_parameter_id');

            return [
                'enrollment_item_id' => $item->id,
                'status'             => $item->status,
                'final_grade'        => $item->final_grade,
                'subject' => [
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
}