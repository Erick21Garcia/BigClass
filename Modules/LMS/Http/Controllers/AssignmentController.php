<?php

namespace Modules\LMS\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Modules\LMS\Http\Requests\GradeSubmissionRequest;
use Modules\LMS\Http\Requests\StoreAssignmentRequest;
use Modules\LMS\Http\Requests\SubmitAssignmentRequest;
use Modules\LMS\Models\Assignment;
use Modules\LMS\Models\AssignmentSubmission;
use Modules\LMS\Models\Unit;
use Modules\LMS\Services\AssignmentService;
use Modules\LMS\Services\SubmissionService;
use Modules\People\Models\Student;

class AssignmentController extends Controller
{
    public function __construct(
        private readonly AssignmentService $assignmentService,
        private readonly SubmissionService $submissionService,
    ) {}

    public function store(StoreAssignmentRequest $request, Unit $unit): RedirectResponse
    {
        $this->assignmentService->create($unit, $request->validated());

        return back()->with('success', 'Tarea creada correctamente.');
    }

    public function update(StoreAssignmentRequest $request, Assignment $assignment): RedirectResponse
    {
        $this->assignmentService->update($assignment, $request->validated());

        return back()->with('success', 'Tarea actualizada.');
    }

    public function destroy(Assignment $assignment): RedirectResponse
    {
        $this->assignmentService->deactivate($assignment);

        return back()->with('success', 'Tarea eliminada.');
    }

    public function submit(SubmitAssignmentRequest $request, Assignment $assignment): RedirectResponse
    {
        $student = Student::whereHas('person', fn ($q) => $q->where('user_id', $request->user()->id))
            ->firstOrFail();

        $this->submissionService->submit($assignment, $student, $request->file('files'));

        return back()->with('success', 'Tarea entregada correctamente.');
    }

    public function grade(GradeSubmissionRequest $request, AssignmentSubmission $submission): RedirectResponse
    {
        try {
            $this->submissionService->grade(
                $submission,
                (float) $request->validated('grade'),
                $request->validated('feedback'),
                $request->user()->id
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['grade' => $e->getMessage()]);
        }

        return back()->with('success', 'Calificación guardada.');
    }
}