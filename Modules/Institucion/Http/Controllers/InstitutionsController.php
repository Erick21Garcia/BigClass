<?php

namespace Modules\Institucion\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Modules\Institucion\Http\Requests\StoreInstitutionRequest;
use Modules\Institucion\Http\Requests\UpdateInstitutionRequest;
use Modules\Institucion\Models\Institution;
use Modules\Institucion\Services\InstitutionService;

class InstitutionsController extends Controller
{
    public function __construct(
        private InstitutionService $institutionService
    ) {}

    public function index()
    {
        $institutions = Institution::query()
            ->orderBy('name')
            ->get()
            ->map(fn ($institution) => [
                'id'       => $institution->id,
                'name'     => $institution->name,
                'type'     => $institution->type,
                'code'     => $institution->code,
                'acronym'  => $institution->acronym,
                'email'    => $institution->email,
                'phone'    => $institution->phone,
                'address'  => $institution->address,
                'city'     => $institution->city,
                'province' => $institution->province,
                'country'  => $institution->country,
                'active'   => $institution->active,
                'created_at' => $institution->created_at->format('Y-m-d H:i'),
            ]);

        return Inertia::render('institutions/Index', compact('institutions'));
    }

    public function store(StoreInstitutionRequest $request)
    {
        $this->institutionService->create($request->validated());

        return redirect()
            ->route('institutions.index')
            ->with('success', 'Institución creada exitosamente');
    }

    public function show(Institution $institution)
    {
        $institution->load('faculties');

        return Inertia::render('institutions/Show', [
            'institution' => [
                'id'       => $institution->id,
                'name'     => $institution->name,
                'type'     => $institution->type,
                'code'     => $institution->code,
                'acronym'  => $institution->acronym,
                'email'    => $institution->email,
                'phone'    => $institution->phone,
                'address'  => $institution->address,
                'city'     => $institution->city,
                'province' => $institution->province,
                'country'  => $institution->country,
                'active'   => $institution->active,
                'faculties' => $institution->faculties->map(fn ($f) => [
                    'id'          => $f->id,
                    'name'        => $f->name,
                    'code'        => $f->code,
                    'description' => $f->description,
                    'dean_name'   => $f->dean_name,
                    'active'      => $f->active,
                ]),
            ],
        ]);
    }

    public function edit(Institution $institution)
    {
        return Inertia::render('institutions/Edit', [
            'institution' => [
                'id'       => $institution->id,
                'name'     => $institution->name,
                'type'     => $institution->type,
                'code'     => $institution->code,
                'acronym'  => $institution->acronym,
                'email'    => $institution->email,
                'phone'    => $institution->phone,
                'address'  => $institution->address,
                'city'     => $institution->city,
                'province' => $institution->province,
                'country'  => $institution->country,
                'active'   => $institution->active,
            ],
        ]);
    }

    public function update(UpdateInstitutionRequest $request, Institution $institution)
    {
        $this->institutionService->update($institution, $request->validated());

        return redirect()
            ->route('institutions.index')
            ->with('success', 'Institución actualizada exitosamente');
    }

    public function destroy(Institution $institution)
    {
        try {
            $this->institutionService->delete($institution);
        } catch (\DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('institutions.index')
            ->with('success', 'Institución eliminada exitosamente');
    }
}