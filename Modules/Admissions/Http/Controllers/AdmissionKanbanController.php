<?php

namespace Modules\Admissions\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Admissions\Http\Requests\MoveApplicantStatusRequest;
use Modules\Admissions\Models\Applicant;
use Modules\Admissions\Services\AdmissionReviewService;

class AdmissionKanbanController extends Controller
{
    public function __construct(
        private readonly AdmissionReviewService $reviewService
    ) {}

    public function index(): Response
    {
        $applicants = Applicant::inKanban()
            ->with('career', 'documents')
            ->get();

        return Inertia::render('admissions/staff/Kanban', [
            'columns' => collect(['recibida', 'en_revision', 'aprobada', 'rechazada'])->map(fn ($status) => [
                'status' => $status,
                'applicants' => $applicants->where('status', $status)->map(fn (Applicant $a) => [
                    'id'              => $a->id,
                    'full_name'       => $a->full_name,
                    'career_name'     => $a->career->name,
                    'submitted_at'    => $a->submitted_at,
                    'documents'       => $a->documents->map(fn ($d) => [
                        'type'          => $d->type,
                        'original_name' => $d->original_name,
                        'download_url'  => $d->downloadUrl(30),
                    ]),
                ])->values(),
            ]),
        ]);
    }

    public function move(MoveApplicantStatusRequest $request, Applicant $applicant): RedirectResponse
    {
        try {
            $this->reviewService->moveStatus(
                $applicant,
                $request->validated('status'),
                $request->validated('notes'),
                $request->user()->id
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('success', 'Estado actualizado.');
    }
}