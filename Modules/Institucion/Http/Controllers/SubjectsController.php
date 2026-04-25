<?php

namespace Modules\Institucion\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Institucion\Http\Requests\StoreSubjectRequest;
use Modules\Institucion\Http\Requests\UpdateSubjectRequest;
use Modules\Institucion\Models\Subject;
use Modules\Institucion\Models\SubjectPrerequisite;
use Modules\Institucion\Services\SubjectService;

class SubjectsController extends Controller
{
    public function __construct(
        private SubjectService $subjectService
    ) {}

    public function index()
    {
        $subjects = Subject::query()
            ->withCount('prerequisiteSubjects')
            ->with('prerequisiteSubjects:id,name,code')
            ->orderBy('name')
            ->get()
            ->map(fn ($subject) => [
                'id'                          => $subject->id,
                'code'                        => $subject->code,
                'name'                        => $subject->name,
                'description'                 => $subject->description,
                'credits'                     => $subject->credits,
                'active'                      => $subject->active,
                'prerequisite_subjects_count' => $subject->prerequisite_subjects_count,
                'prerequisite_subjects'       => $subject->prerequisiteSubjects->map(fn ($s) => [
                    'id'   => $s->id,
                    'name' => $s->name,
                    'code' => $s->code,
                ]),
                'created_at'                  => $subject->created_at->format('Y-m-d H:i'),
            ]);

        $allSubjects = Subject::where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('subjects/Index', [
            'subjects'    => $subjects,
            'allSubjects' => $allSubjects,
        ]);
    }

    public function store(StoreSubjectRequest $request)
    {
        $this->subjectService->create($request->validated());

        return redirect()->back()->with('success', 'Materia creada exitosamente.');
    }

    public function show(Subject $subject)
    {
        $subject->load(['prerequisiteSubjects', 'isPrerequisiteOf']);

        $allSubjects = Subject::where('active', true)
            ->where('id', '!=', $subject->id)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('subjects/Show', [
            'subject' => [
                'id'                    => $subject->id,
                'code'                  => $subject->code,
                'name'                  => $subject->name,
                'description'           => $subject->description,
                'credits'               => $subject->credits,
                'active'                => $subject->active,
                'prerequisite_subjects' => $subject->prerequisiteSubjects->map(fn ($s) => [
                    'id'      => $s->id,
                    'code'    => $s->code,
                    'name'    => $s->name,
                    'credits' => $s->credits,
                ]),
                'is_prerequisite_of'    => $subject->isPrerequisiteOf->map(fn ($s) => [
                    'id'      => $s->id,
                    'code'    => $s->code,
                    'name'    => $s->name,
                    'credits' => $s->credits,
                ]),
            ],
            'allSubjects' => $allSubjects,
        ]);
    }

    public function update(UpdateSubjectRequest $request, Subject $subject)
    {
        $this->subjectService->update($subject, $request->validated());

        return redirect()->back()->with('success', 'Materia actualizada exitosamente.');
    }

    public function destroy(Subject $subject)
    {
        try {
            $this->subjectService->delete($subject);
        } catch (\DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->route('subjects.index')->with('success', 'Materia eliminada exitosamente.');
    }

    public function attachPrerequisite(Request $request, Subject $subject)
    {
        $request->validate([
            'prerequisite_id' => [
                'required',
                'integer',
                'exists:subjects,id',
                'different:' . $subject->id,
            ],
        ], [
            'prerequisite_id.required'  => 'Debes seleccionar un prerequisito.',
            'prerequisite_id.exists'    => 'La materia seleccionada no existe.',
            'prerequisite_id.different' => 'Una materia no puede ser prerequisito de sí misma.',
        ]);

        $alreadyExists = SubjectPrerequisite::where('subject_id', $subject->id)
            ->where('prerequisite_id', $request->prerequisite_id)
            ->exists();

        if ($alreadyExists) {
            return back()->withErrors(['prerequisite_id' => 'Esta materia ya es prerequisito.']);
        }

        SubjectPrerequisite::create([
            'subject_id'      => $subject->id,
            'prerequisite_id' => $request->prerequisite_id,
            'active'          => true,
        ]);

        return redirect()->back()->with('success', 'Prerequisito agregado exitosamente.');
    }

    public function detachPrerequisite(Subject $subject, Subject $prerequisite)
    {
        SubjectPrerequisite::where('subject_id', $subject->id)
            ->where('prerequisite_id', $prerequisite->id)
            ->delete();

        return redirect()->back()->with('success', 'Prerequisito eliminado exitosamente.');
    }
}