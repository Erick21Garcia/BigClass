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

    public function create(): Response
    {
        return Inertia::render('admissions/Register', [
            'careers'            => Career::where('active', true)->get(['id', 'name']),
            'turnstile_site_key' => config('services.turnstile.site_key'),
        ]);
    }

    public function store(RegisterApplicantRequest $request): RedirectResponse
    {
        $applicant = $this->registrationService->register(
            collect($request->validated())->except('captcha_token')->all()
        );

        Auth::login($applicant->user);

        return redirect()->route('admissions.applicant.dashboard');
    }
}