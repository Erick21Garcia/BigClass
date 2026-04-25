<?php

namespace Modules\Institucion\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Modules\Institucion\Http\Requests\StoreCareerRequest;
use Modules\Institucion\Http\Requests\UpdateCareerRequest;
use Modules\Institucion\Models\Career;
use Modules\Institucion\Models\Faculty;
use Modules\Institucion\Models\Institution;
use Modules\Institucion\Services\CareerService;

class CareersController extends Controller
{
    public function __construct(
        private CareerService $careerService
    ) {}

    public function index()
    {
        $careers = Career::query()
            ->with(['faculty', 'faculty.institution'])
            ->orderBy('name')
            ->get()
            ->map(fn ($career) => [
                'id'                 => $career->id,
                'name'               => $career->name,
                'code'               => $career->code,
                'description'        => $career->description,
                'modality'           => $career->modality,
                'duration_semesters' => $career->duration_semesters,
                'title_awarded'      => $career->title_awarded,
                'active'             => $career->active,
                'faculty'            => $career->faculty?->name,
                'institution'        => $career->faculty?->institution?->name,
                'faculty_id'         => $career->faculty_id,
                'created_at'         => $career->created_at->format('Y-m-d H:i'),
            ]);

        return Inertia::render('careers/Index', array_merge(
            compact('careers'),
            $this->getFormData()
        ));
    }

    public function store(StoreCareerRequest $request)
    {
        $this->careerService->create($request->validated());

        return redirect()
            ->route('careers.index')
            ->with('success', 'Carrera creada exitosamente');
    }

    public function show(Career $career)
    {
        $career->load('faculty.institution', 'semesters');

        return Inertia::render('careers/Show', [
            'career' => [
                'id'                 => $career->id,
                'name'               => $career->name,
                'code'               => $career->code,
                'description'        => $career->description,
                'modality'           => $career->modality,
                'duration_semesters' => $career->duration_semesters,
                'title_awarded'      => $career->title_awarded,
                'active'             => $career->active,
                'faculty' => [
                    'id'          => $career->faculty->id,
                    'name'        => $career->faculty->name,
                    'institution' => [
                        'id'   => $career->faculty->institution->id,
                        'name' => $career->faculty->institution->name,
                    ],
                ],
                'semesters' => $career->semesters->map(fn ($s) => [
                    'id'     => $s->id,
                    'name'   => $s->name,
                    'number' => $s->number,
                    'active' => $s->active,
                ]),
            ],
        ]);
    }

    public function edit(Career $career)
    {
        return Inertia::render('careers/Edit', array_merge(
            $this->getFormData(),
            [
                'career' => [
                    'id'                 => $career->id,
                    'faculty_id'         => $career->faculty_id,
                    'name'               => $career->name,
                    'code'               => $career->code,
                    'description'        => $career->description,
                    'modality'           => $career->modality,
                    'duration_semesters' => $career->duration_semesters,
                    'title_awarded'      => $career->title_awarded,
                    'active'             => $career->active,
                ],
            ]
        ));
    }

    public function update(UpdateCareerRequest $request, Career $career)
    {
        $this->careerService->update($career, $request->validated());

        return redirect()
            ->route('careers.index')
            ->with('success', 'Carrera actualizada exitosamente');
    }

    public function destroy(Career $career)
    {
        try {
            $this->careerService->delete($career);
        } catch (\DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('careers.index')
            ->with('success', 'Carrera eliminada exitosamente');
    }

    private function getFormData(): array
    {
        return [
            'institutions' => Institution::orderBy('name')->get(['id', 'name']),
            'faculties'    => Faculty::orderBy('name')->get(['id', 'name', 'institution_id']),
        ];
    }
}