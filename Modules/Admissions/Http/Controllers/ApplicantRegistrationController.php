<?php

namespace Modules\Admissions\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Admissions\Http\Requests\RegisterApplicantRequest;
use Modules\Admissions\Services\ApplicantRegistrationService;
use Modules\Institucion\Models\Career;

class ApplicantRegistrationController extends Controller
{
    public function __construct(
        private readonly ApplicantRegistrationService $registrationService
    ) {}

    /**
     * Formulario público de registro — Punto 1.
     */
    public function create(): Response
    {
        return Inertia::render('admissions/Register', [
            'careers' => Career::where('active', true)->get(['id', 'name']),
        ]);
    }

    public function store(RegisterApplicantRequest $request): RedirectResponse
    {
        $applicant = $this->registrationService->register($request->validated());

        Auth::login($applicant->user);

        // Punto 2 (formulario de postulación completo) define esta ruta
        // de destino — por ahora apunta al panel del aspirante.
        return redirect()->route('admissions.applicant.dashboard');
    }
}