<?php

namespace Modules\Admissions\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Admissions\Http\Requests\UpdateAdmissionsSettingsRequest;
use Modules\Admissions\Models\AdmissionsSetting;

class AdmissionsSettingsController extends Controller
{
    public function edit(): Response
    {
        $settings = AdmissionsSetting::current();

        return Inertia::render('admissions/staff/Settings', [
            'settings' => $settings->only(['abandon_after_days', 'retention_after_months']),
        ]);
    }

    public function update(UpdateAdmissionsSettingsRequest $request): RedirectResponse
    {
        AdmissionsSetting::current()->update($request->validated());

        return back()->with('success', 'Configuración actualizada.');
    }
}