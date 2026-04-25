<?php

namespace Modules\People\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Modules\People\Http\Requests\StoreStudentRequest;
use Modules\People\Http\Requests\UpdateStudentRequest;
use Modules\People\Models\Person;
use Modules\People\Models\Student;
use Modules\People\Services\StudentService;

class StudentsController extends Controller
{
    public function __construct(
        private StudentService $studentService
    ) {}

    public function index()
    {
        $students = Student::query()
            ->with('person')
            ->orderBy('enrollment_number')
            ->get()
            ->map(fn ($student) => [
                'id'                    => $student->id,
                'full_name'             => $student->person?->full_name,
                'identification_number' => $student->person?->identification_number,
                'enrollment_number'     => $student->enrollment_number,
                'active'                => $student->active,
                'created_at'            => $student->created_at->format('Y-m-d H:i'),
                'person_id'             => $student->person_id,
            ]);

        return Inertia::render('students/Index', array_merge(
            compact('students'),
            $this->getFormData()
        ));
    }

    public function store(StoreStudentRequest $request)
    {
        $this->studentService->create($request->validated());

        return redirect()
            ->route('students.index')
            ->with('success', 'Estudiante creado exitosamente');
    }

    public function edit(Student $student)
    {
        return Inertia::render('students/Edit', array_merge(
            $this->getFormData(),
            [
                'student' => [
                    'id'                => $student->id,
                    'person_id'         => $student->person_id,
                    'enrollment_number' => $student->enrollment_number,
                    'active'            => $student->active,
                ],
            ]
        ));
    }

    public function update(UpdateStudentRequest $request, Student $student)
    {
        $this->studentService->update($student, $request->validated());

        return redirect()
            ->route('students.index')
            ->with('success', 'Estudiante actualizado exitosamente');
    }

    public function destroy(Student $student)
    {
        try {
            $this->studentService->delete($student);
        } catch (\DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('students.index')
            ->with('success', 'Estudiante eliminado exitosamente');
    }

    private function getFormData(): array
    {
        return [
            'people' => Person::orderBy('first_surname')
                            ->orderBy('second_surname')
                            ->get(['id', 'first_name', 'second_name', 'first_surname', 'second_surname', 'identification_number'])
                            ->map(fn ($p) => [
                                'id'                    => $p->id,
                                'name'                  => $p->full_name,
                                'identification_number' => $p->identification_number,
                            ]),
        ];
    }
}