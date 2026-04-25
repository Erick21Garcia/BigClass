<?php

namespace Modules\Institucion\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Modules\Institucion\Http\Requests\StoreCurriculumRequest;
use Modules\Institucion\Http\Requests\UpdateCurriculumRequest;
use Modules\Institucion\Models\Career;
use Modules\Institucion\Models\Curriculum;
use Modules\Institucion\Models\Semester;
use Modules\Institucion\Models\Subject;
use Modules\Institucion\Services\CurriculumService;

class CurriculaController extends Controller
{
    public function __construct(
        private CurriculumService $curriculumService
    ) {}

    public function index()
    {
        $curricula = Curriculum::query()
            ->with(['career', 'subject', 'semester'])
            ->orderBy('career_id')
            ->get()
            ->map(fn ($curriculum) => [
                'id'           => $curriculum->id,
                'career'       => $curriculum->career?->name,
                'career_id'    => $curriculum->career_id,
                'subject'      => $curriculum->subject?->name,
                'subject_id'   => $curriculum->subject_id,
                'semester'     => $curriculum->semester?->name,
                'semester_id'  => $curriculum->semester_id,
                'is_mandatory' => $curriculum->is_mandatory,
                'active'       => $curriculum->active,
                'created_at'   => $curriculum->created_at->format('Y-m-d H:i'),
            ]);

        return Inertia::render('curricula/Index', array_merge(
            compact('curricula'),
            $this->getFormData()
        ));
    }

    /**
     * Página dedicada para gestionar la malla curricular de una carrera.
     * Recibe career_id por query string.
     */
    public function manage()
    {
        $careerId = request()->integer('career_id');

        abort_if(! $careerId, 400, 'Se requiere una carrera para gestionar la malla.');

        $career = Career::with('faculty.institution')->findOrFail($careerId);

        $semesters = Semester::where('career_id', $careerId)
            ->orderBy('number')
            ->get();

        $curricula = Curriculum::where('career_id', $careerId)
            ->where('active', true)
            ->with('subject')
            ->get()
            ->groupBy('semester_id');

        $semestersWithSubjects = $semesters->map(fn ($semester) => [
            'id'       => $semester->id,
            'name'     => $semester->name,
            'number'   => $semester->number,
            'active'   => $semester->active,
            'subjects' => ($curricula->get($semester->id) ?? collect())->map(fn ($c) => [
                'curriculum_id' => $c->id,
                'subject_id'    => $c->subject->id,
                'subject_name'  => $c->subject->name,
                'subject_code'  => $c->subject->code,
                'credits'       => $c->subject->credits,
                'is_mandatory'  => $c->is_mandatory,
            ])->values()->all(),
        ])->values()->all();

        $subjects = Subject::where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'credits']);

        return Inertia::render('curricula/Manage', [
            'career' => [
                'id'   => $career->id,
                'name' => $career->name,
                'faculty' => [
                    'id'   => $career->faculty->id,
                    'name' => $career->faculty->name,
                    'institution' => [
                        'id'   => $career->faculty->institution->id,
                        'name' => $career->faculty->institution->name,
                    ],
                ],
            ],
            'semesters' => $semestersWithSubjects,
            'subjects'  => $subjects,
        ]);
    }

    public function store(StoreCurriculumRequest $request)
    {
        $this->curriculumService->create($request->validated());

        $redirectTo = $request->input('redirect_to', 'curricula.index');

        if ($redirectTo === 'manage') {
            return redirect()
                ->route('curricula.manage', ['career_id' => $request->validated()['career_id']])
                ->with('success', 'Materia asignada exitosamente.');
        }

        return redirect()
            ->route('curricula.index')
            ->with('success', 'Currículo creado exitosamente.');
    }

    public function destroy(Curriculum $curriculum)
    {
        $careerId = $curriculum->career_id;

        try {
            $this->curriculumService->delete($curriculum);
        } catch (\DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->back()
            ->with('success', 'Materia removida de la malla exitosamente.');
    }

    public function show(Curriculum $curriculum)
    {
        $curriculum->load(['career', 'subject', 'semester']);

        return Inertia::render('curricula/Show', [
            'curriculum' => [
                'id'           => $curriculum->id,
                'is_mandatory' => $curriculum->is_mandatory,
                'active'       => $curriculum->active,
                'career'       => [
                    'id'   => $curriculum->career->id,
                    'name' => $curriculum->career->name,
                ],
                'subject' => [
                    'id'          => $curriculum->subject->id,
                    'name'        => $curriculum->subject->name,
                    'code'        => $curriculum->subject->code,
                    'credits'     => $curriculum->subject->credits,
                    'description' => $curriculum->subject->description,
                ],
                'semester' => [
                    'id'   => $curriculum->semester->id,
                    'name' => $curriculum->semester->name,
                ],
            ],
        ]);
    }

    public function edit(Curriculum $curriculum)
    {
        return Inertia::render('curricula/Edit', array_merge(
            $this->getFormData(),
            [
                'curriculum' => [
                    'id'           => $curriculum->id,
                    'career_id'    => $curriculum->career_id,
                    'subject_id'   => $curriculum->subject_id,
                    'semester_id'  => $curriculum->semester_id,
                    'is_mandatory' => $curriculum->is_mandatory,
                    'active'       => $curriculum->active,
                ],
            ]
        ));
    }

    public function update(UpdateCurriculumRequest $request, Curriculum $curriculum)
    {
        $this->curriculumService->update($curriculum, $request->validated());

        return redirect()
            ->route('curricula.index')
            ->with('success', 'Currículo actualizado exitosamente.');
    }

    private function getFormData(): array
    {
        return [
            'careers'   => Career::orderBy('name')->get(['id', 'name']),
            'subjects'  => Subject::where('active', true)->orderBy('name')->get(['id', 'name', 'code']),
            'semesters' => Semester::orderBy('name')->get(['id', 'name']),
        ];
    }
}