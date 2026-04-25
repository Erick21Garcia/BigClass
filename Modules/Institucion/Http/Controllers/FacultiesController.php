<?php

namespace Modules\Institucion\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Modules\Institucion\Http\Requests\StoreFacultyRequest;
use Modules\Institucion\Http\Requests\UpdateFacultyRequest;
use Modules\Institucion\Models\Faculty;
use Modules\Institucion\Models\Institution;
use Modules\Institucion\Services\FacultyService;

class FacultiesController extends Controller
{
    public function __construct(
        private FacultyService $facultyService
    ) {}

    public function index()
    {
        $faculties = Faculty::query()
            ->with('institution')
            ->orderBy('name')
            ->get()
            ->map(fn ($faculty) => [
                'id'             => $faculty->id,
                'name'           => $faculty->name,
                'code'           => $faculty->code,
                'description'    => $faculty->description,
                'dean_name'      => $faculty->dean_name,
                'active'         => $faculty->active,
                'institution'    => $faculty->institution?->name,
                'institution_id' => $faculty->institution_id,
                'created_at'     => $faculty->created_at->format('Y-m-d H:i'),
            ]);

        return Inertia::render('faculties/Index', array_merge(
            compact('faculties'),
            $this->getFormData()
        ));
    }

    public function store(StoreFacultyRequest $request)
    {
        $this->facultyService->create($request->validated());

        return redirect()
            ->route('faculties.index')
            ->with('success', 'Facultad creada exitosamente');
    }

    public function show(Faculty $faculty)
    {
        $faculty->load('institution', 'careers');

        return Inertia::render('faculties/Show', [
            'faculty' => [
                'id'          => $faculty->id,
                'name'        => $faculty->name,
                'code'        => $faculty->code,
                'description' => $faculty->description,
                'dean_name'   => $faculty->dean_name,
                'active'      => $faculty->active,
                'institution' => [
                    'id'   => $faculty->institution->id,
                    'name' => $faculty->institution->name,
                ],
                'careers' => $faculty->careers->map(fn ($c) => [
                    'id'                 => $c->id,
                    'name'               => $c->name,
                    'code'               => $c->code,
                    'description'        => $c->description,
                    'modality'           => $c->modality,
                    'duration_semesters' => $c->duration_semesters,
                    'title_awarded'      => $c->title_awarded,
                    'active'             => $c->active,
                ]),
            ],
        ]);
    }

    public function edit(Faculty $faculty)
    {
        return Inertia::render('faculties/Edit', array_merge(
            $this->getFormData(),
            [
                'faculty' => [
                    'id'             => $faculty->id,
                    'institution_id' => $faculty->institution_id,
                    'name'           => $faculty->name,
                    'code'           => $faculty->code,
                    'description'    => $faculty->description,
                    'dean_name'      => $faculty->dean_name,
                    'active'         => $faculty->active,
                ],
            ]
        ));
    }

    public function update(UpdateFacultyRequest $request, Faculty $faculty)
    {
        $this->facultyService->update($faculty, $request->validated());

        return redirect()
            ->route('faculties.index')
            ->with('success', 'Facultad actualizada exitosamente');
    }

    public function destroy(Faculty $faculty)
    {
        try {
            $this->facultyService->delete($faculty);
        } catch (\DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('faculties.index')
            ->with('success', 'Facultad eliminada exitosamente');
    }

    private function getFormData(): array
    {
        return [
            'institutions' => Institution::orderBy('name')->get(['id', 'name']),
        ];
    }
}