<?php

use Illuminate\Support\Facades\Route;
use Modules\Admissions\Http\Controllers\ApplicantRegistrationController;
use Modules\Admissions\Http\Controllers\ApplicantDashboardController;
use Modules\Admissions\Http\Controllers\ApplicantProfileController;
use Modules\Admissions\Http\Controllers\ApplicantDocumentController;
use Modules\Admissions\Http\Controllers\AdmissionKanbanController;
use Modules\Admissions\Http\Controllers\AdmissionsSettingsController;
use Modules\Admissions\Http\Controllers\ApplicantEmailVerificationController;
use Modules\Admissions\Http\Middleware\EnsureApplicantEmailIsVerified;

Route::middleware(['guest'])->prefix('admisiones')->group(function () {
    Route::get('/registro', [ApplicantRegistrationController::class, 'create'])
        ->name('admissions.register');

    Route::post('/registro', [ApplicantRegistrationController::class, 'store'])
        ->middleware('throttle:5,60')
        ->name('admissions.register.store');
});

Route::middleware(['auth'])->prefix('admisiones')->group(function () {
    Route::get('/mi-postulacion', [ApplicantDashboardController::class, 'show'])
        ->name('admissions.applicant.dashboard');

    Route::put('/mi-postulacion/perfil', [ApplicantProfileController::class, 'update'])
        ->name('admissions.applicant.profile.update');

    Route::post('/mi-postulacion/documentos', [ApplicantDocumentController::class, 'store'])
        ->middleware(EnsureApplicantEmailIsVerified::class)
        ->name('admissions.applicant.documents.store');

    Route::post('/mi-postulacion/enviar', [ApplicantDashboardController::class, 'submit'])
        ->middleware(EnsureApplicantEmailIsVerified::class)
        ->name('admissions.applicant.submit');

    Route::post('/verificacion/reenviar', [ApplicantEmailVerificationController::class, 'resend'])
        ->middleware('throttle:3,10')
        ->name('admissions.email.resend');

    Route::get('/revision', [AdmissionKanbanController::class, 'index'])
        ->name('admissions.kanban.index');

    Route::post('/revision/{applicant}/mover', [AdmissionKanbanController::class, 'move'])
        ->name('admissions.kanban.move');

    Route::get('/configuracion', [AdmissionsSettingsController::class, 'edit'])
        ->name('admissions.settings.edit');

    Route::put('/configuracion', [AdmissionsSettingsController::class, 'update'])
        ->name('admissions.settings.update');
});

Route::get('/admisiones/documentos/{document}/descargar', [ApplicantDocumentController::class, 'download'])
    ->name('admissions.documents.download')
    ->middleware('signed');

Route::get('/admisiones/verificar/{user}/{hash}', [ApplicantEmailVerificationController::class, 'verify'])
    ->middleware('signed')
    ->name('admissions.email.verify');