<?php

use Illuminate\Support\Facades\Route;
use Modules\Admissions\Http\Controllers\ApplicantRegistrationController;
use Modules\Admissions\Http\Controllers\ApplicantDashboardController;
use Modules\Admissions\Http\Controllers\ApplicantProfileController;
use Modules\Admissions\Http\Controllers\ApplicantDocumentController;
use Modules\Admissions\Http\Controllers\AdmissionKanbanController;
use Modules\Admissions\Http\Controllers\AdmissionsSettingsController;

// Público — sin middleware 'auth', el aspirante todavía no existe como
// usuario cuando entra aquí.
Route::middleware(['guest'])->prefix('admisiones')->group(function () {
    Route::get('/registro', [ApplicantRegistrationController::class, 'create'])
        ->name('admissions.register');

    Route::post('/registro', [ApplicantRegistrationController::class, 'store'])
        ->name('admissions.register.store');
});

// A partir de aquí, el aspirante ya tiene sesión (Auth::login() lo dejó
// logueado en el registro) — Punto 2: panel del aspirante, datos
// personales, subir documentos, enviar postulación.
Route::middleware(['auth'])->prefix('admisiones')->group(function () {
    Route::get('/mi-postulacion', [ApplicantDashboardController::class, 'show'])
        ->name('admissions.applicant.dashboard');

    Route::put('/mi-postulacion/perfil', [ApplicantProfileController::class, 'update'])
        ->name('admissions.applicant.profile.update');

    Route::post('/mi-postulacion/documentos', [ApplicantDocumentController::class, 'store'])
        ->name('admissions.applicant.documents.store');

    Route::post('/mi-postulacion/enviar', [ApplicantDashboardController::class, 'submit'])
        ->name('admissions.applicant.submit');

    // --- Revisión administrativa (Kanban) — Punto 4 ---
    Route::get('/revision', [AdmissionKanbanController::class, 'index'])
        ->name('admissions.kanban.index');

    Route::post('/revision/{applicant}/mover', [AdmissionKanbanController::class, 'move'])
        ->name('admissions.kanban.move');

    // --- Configuración (higiene de datos) — Punto 1 ---
    Route::get('/configuracion', [AdmissionsSettingsController::class, 'edit'])
        ->name('admissions.settings.edit');

    Route::put('/configuracion', [AdmissionsSettingsController::class, 'update'])
        ->name('admissions.settings.update');
});

// Firmada — misma razón que en LMS: la seguridad la da la firma de la
// URL, no la sesión, así funciona si se abre en pestaña nueva.
Route::get('/admisiones/documentos/{document}/descargar', [ApplicantDocumentController::class, 'download'])
    ->name('admissions.documents.download')
    ->middleware('signed');