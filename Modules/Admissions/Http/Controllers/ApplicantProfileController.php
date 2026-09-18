<?php

namespace Modules\Admissions\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Admissions\Http\Requests\UpdateApplicantProfileRequest;
use Modules\Admissions\Services\ApplicantService;

class ApplicantProfileController extends Controller
{
    public function __construct(
        private readonly ApplicantService $applicantService
    ) {}

    public function update(UpdateApplicantProfileRequest $request): RedirectResponse
    {
        $applicant = ApplicantDashboardController::resolveApplicant($request);

        if ($applicant->status !== 'borrador') {
            return back()->withErrors(['profile' => 'No puedes editar una postulación ya enviada.']);
        }

        $this->applicantService->updatePersonalData($applicant, $request->validated());

        return back()->with('success', 'Datos guardados.');
    }
}