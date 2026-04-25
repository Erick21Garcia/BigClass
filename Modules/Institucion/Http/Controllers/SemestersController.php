<?php

namespace Modules\Institucion\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Modules\Academic\Models\Enrollment;
use Modules\Institucion\Http\Requests\StoreSemesterRequest;
use Modules\Institucion\Http\Requests\UpdateSemesterRequest;
use Modules\Institucion\Models\Career;
use Modules\Institucion\Models\Faculty;
use Modules\Institucion\Models\Institution;
use Modules\Institucion\Models\Semester;
use Modules\Institucion\Services\SemesterService;

class SemestersController extends Controller
{
    public function __construct(
        private SemesterService $semesterService
    ) {}

    public function index()
    {
        $semesters = Semester::query()
            ->with(['career', 'career.faculty', 'career.faculty.institution'])
            ->orderBy('name')
            ->get()
            ->map(fn ($semester) => [
                'id'          => $semester->id,
                'name'        => $semester->name,
                'number'      => $semester->number,
                'active'      => $semester->active,
                'career'      => $semester->career?->name,
                'faculty'     => $semester->career?->faculty?->name,
                'institution' => $semester->career?->faculty?->institution?->name,
                'career_id'   => $semester->career_id,
                'created_at'  => $semester->created_at->format('Y-m-d H:i'),
            ]);

        return Inertia::render('semesters/Index', array_merge(
            compact('semesters'),
            $this->getFormData()
        ));
    }

    public function store(StoreSemesterRequest $request)
    {
        $this->semesterService->create($request->validated());

        return redirect()
            ->route('semesters.index')
            ->with('success', 'Semestre creado exitosamente');
    }

    public function show(Semester $semester)
    {
        $semester->load('career.faculty.institution');

        $enrollments = $semester->enrollments()
            ->with(['student.person', 'academicPeriod'])
            ->get()
            ->map(fn ($enrollment) => [
                'id'              => $enrollment->id,
                'enrollment_date' => $enrollment->enrollment_date?->format('Y-m-d'),
                'type'            => $enrollment->type,
                'status'          => $enrollment->status,
                'student' => [
                    'id'                => $enrollment->student->id,
                    'enrollment_number' => $enrollment->student->enrollment_number,
                    'full_name'         => $enrollment->student->person->full_name,
                ],
                'academic_period' => [
                    'id'   => $enrollment->academicPeriod->id,
                    'name' => $enrollment->academicPeriod->name,
                ],
            ]);

        return Inertia::render('semesters/Show', [
            'semester' => [
                'id'     => $semester->id,
                'name'   => $semester->name,
                'number' => $semester->number,
                'active' => $semester->active,
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
            'enrollments'    => $enrollments,
            'academicPeriods' => \Modules\Academic\Models\AcademicPeriod::orderBy('start_date', 'desc')->get(['id', 'name']),
        ]);
    }

    public function edit(Semester $semester)
    {
        return Inertia::render('semesters/Edit', array_merge(
            $this->getFormData(),
            [
                'semester' => [
                    'id'        => $semester->id,
                    'career_id' => $semester->career_id,
                    'number'    => $semester->number,
                    'name'      => $semester->name,
                    'active'    => $semester->active,
                ],
            ]
        ));
    }

    public function update(UpdateSemesterRequest $request, Semester $semester)
    {
        $this->semesterService->update($semester, $request->validated());

        return redirect()
            ->route('semesters.index')
            ->with('success', 'Semestre actualizado exitosamente');
    }

    public function destroy(Semester $semester)
    {
        try {
            $this->semesterService->delete($semester);
        } catch (\DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('semesters.index')
            ->with('success', 'Semestre eliminado exitosamente');
    }

    private function getFormData(): array
    {
        return [
            'institutions' => Institution::orderBy('name')->get(['id', 'name']),
            'faculties'    => Faculty::orderBy('name')->get(['id', 'name', 'institution_id']),
            'careers'      => Career::orderBy('name')->get(['id', 'name', 'faculty_id']),
        ];
    }
}