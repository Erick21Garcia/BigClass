<?php

namespace Modules\Admissions\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Admissions\Models\Applicant;
use Modules\Admissions\Models\ApplicantDocument;
use Modules\Admissions\Services\ApplicantService;

class ApplicantDashboardController extends Controller
{
    public function __construct(
        private readonly ApplicantService $applicantService
    ) {}

    /**
     * Panel del aspirante — checklist de documentos + estado, Punto 2.
     */
    public function show(Request $request): Response
    {
        $applicant = $this->resolveApplicant($request);
        $applicant->load('documents', 'career');

        $uploadedTypes = $applicant->documents->pluck('type');

        return Inertia::render('admissions/applicant/Dashboard', [
            'applicant' => [
                'status'       => $applicant->status,
                'career_name'  => $applicant->career->name,
                'submitted_at' => $applicant->submitted_at,
                'decision_notes' => $applicant->decision_notes,
                // Los campos personales ya guardados, para precargar el form.
                'profile' => $applicant->only([
                    'first_name', 'second_name', 'first_surname', 'second_surname',
                    'identification_number', 'phone', 'cellphone', 'birthdate', 'place_birth',
                    'main_street', 'secondary_street', 'neighborhood', 'reference',
                    'marital_status_id', 'type_identification_id', 'sex_id', 'nationality_id',
                    'education_level_id', 'countries_id', 'provinces_id', 'cities_id',
                ]),
            ],
            'documents' => collect(ApplicantDocument::TYPES)->map(fn ($type) => [
                'type'     => $type,
                'uploaded' => $uploadedTypes->contains($type),
                'document' => $applicant->documents->firstWhere('type', $type)?->only(['id', 'original_name']),
            ]),
            'can_edit' => $applicant->status === 'borrador',
            // Catálogos para los <select> del formulario de datos personales.
            'catalogs' => [
                'marital_statuses'      => \App\Models\MaritalStatus::get(['id', 'name']),
                'type_identifications'  => \App\Models\TypeIdentification::get(['id', 'name']),
                'sexes'                 => \App\Models\Sex::get(['id', 'name']),
                'nationalities'         => \App\Models\Nationality::get(['id', 'name']),
                'education_levels'      => \App\Models\EducationLevel::get(['id', 'name']),
                'countries'             => \App\Models\Country::get(['id', 'name']),
                'provinces'             => \App\Models\Province::get(['id', 'name']),
                'cities'                => \App\Models\City::get(['id', 'name']),
            ],
        ]);
    }

    public function submit(Request $request): RedirectResponse
    {
        $applicant = $this->resolveApplicant($request);

        try {
            $this->applicantService->submit($applicant);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['submit' => $e->getMessage()]);
        }

        return back()->with('success', 'Postulación enviada correctamente.');
    }

    public static function resolveApplicant(Request $request): Applicant
    {
        return Applicant::where('user_id', $request->user()->id)->firstOrFail();
    }
}