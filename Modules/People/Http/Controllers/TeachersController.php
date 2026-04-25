<?php

namespace Modules\People\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Modules\People\Http\Requests\StoreTeacherRequest;
use Modules\People\Http\Requests\UpdateTeacherRequest;
use Modules\People\Models\Person;
use Modules\People\Models\Teacher;
use Modules\People\Services\TeacherService;
use Modules\Institucion\Models\Institution;

class TeachersController extends Controller
{
    public function __construct(
        private TeacherService $teacherService
    ) {}

    public function index()
    {
        $teachers = Teacher::query()
            ->with(['person', 'institution'])
            ->orderBy('id')
            ->get()
            ->map(fn ($teacher) => [
                'id'                    => $teacher->id,
                'full_name'             => $teacher->person?->full_name,
                'identification_number' => $teacher->person?->identification_number,
                'institution'           => $teacher->institution?->name,
                'hire_date'             => $teacher->hire_date?->format('Y-m-d'),
                'academic_degree'       => $teacher->academic_degree,
                'active'                => $teacher->active,
                'created_at'            => $teacher->created_at->format('Y-m-d H:i'),

                'person_id'             => $teacher->person_id,
                'institution_id'        => $teacher->institution_id,
            ]);

        return Inertia::render('teachers/Index', array_merge(
            compact('teachers'),
            $this->getFormData()
        ));
    }

    public function store(StoreTeacherRequest $request)
    {
        $this->teacherService->create($request->validated());

        return redirect()
            ->route('teachers.index')
            ->with('success', 'Docente creado exitosamente');
    }

    public function edit(Teacher $teacher)
    {
        return Inertia::render('teachers/Edit', array_merge(
            $this->getFormData(),
            [
                'teacher' => [
                    'id'              => $teacher->id,
                    'person_id'       => $teacher->person_id,
                    'institution_id'  => $teacher->institution_id,
                    'hire_date'       => $teacher->hire_date?->format('Y-m-d'),
                    'academic_degree' => $teacher->academic_degree,
                    'active'          => $teacher->active,
                ],
            ]
        ));
    }

    public function update(UpdateTeacherRequest $request, Teacher $teacher)
    {
        $this->teacherService->update($teacher, $request->validated());

        return redirect()
            ->route('teachers.index')
            ->with('success', 'Docente actualizado exitosamente');
    }

    public function destroy(Teacher $teacher)
    {
        try {
            $this->teacherService->delete($teacher);
        } catch (\DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('teachers.index')
            ->with('success', 'Docente eliminado exitosamente');
    }

    private function getFormData(): array
    {
        return [
            'people'       => Person::orderBy('first_surname')
                                ->orderBy('second_surname')
                                ->get(['id', 'first_name', 'second_name', 'first_surname', 'second_surname', 'identification_number'])
                                ->map(fn ($p) => [
                                    'id'                    => $p->id,
                                    'name'                  => $p->full_name,
                                    'identification_number' => $p->identification_number,
                                ]),
            'institutions' => Institution::orderBy('name')->get(['id', 'name']),
        ];
    }
}