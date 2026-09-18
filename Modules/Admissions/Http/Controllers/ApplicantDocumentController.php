<?php

namespace Modules\Admissions\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Admissions\Http\Requests\UploadApplicantDocumentRequest;
use Modules\Admissions\Models\ApplicantDocument;
use Modules\Admissions\Services\ApplicantDocumentService;

class ApplicantDocumentController extends Controller
{
    public function __construct(
        private readonly ApplicantDocumentService $documentService
    ) {}

    public function store(UploadApplicantDocumentRequest $request): RedirectResponse
    {
        $applicant = ApplicantDashboardController::resolveApplicant($request);

        if ($applicant->status !== 'borrador') {
            return back()->withErrors(['document' => 'No puedes modificar documentos de una postulación ya enviada.']);
        }

        $this->documentService->upload($applicant, $request->validated('type'), $request->file('file'));

        return back()->with('success', 'Documento subido.');
    }

    /**
     * Solo accesible vía URL firmada (ApplicantDocument::downloadUrl()),
     * mismo patrón que Resource::download() del LMS.
     */
    public function download(Request $request, ApplicantDocument $document)
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        return $this->documentService->download($document);
    }
}