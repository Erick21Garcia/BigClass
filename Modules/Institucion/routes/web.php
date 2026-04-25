<?php

use Illuminate\Support\Facades\Route;
use Modules\Institucion\Http\Controllers\InstitutionsController;
use Modules\Institucion\Http\Controllers\FacultiesController;
use Modules\Institucion\Http\Controllers\CareersController;
use Modules\Institucion\Http\Controllers\SemestersController;
use Modules\Institucion\Http\Controllers\CurriculaController;
use Modules\Institucion\Http\Controllers\SubjectsController;

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/institutions',                    [InstitutionsController::class, 'index'])->name('institutions.index');
    Route::post('/institutions',                   [InstitutionsController::class, 'store'])->name('institutions.store');
    Route::get('/institutions/{institution}',      [InstitutionsController::class, 'show'])->name('institutions.show');
    Route::put('/institutions/{institution}',      [InstitutionsController::class, 'update'])->name('institutions.update');
    Route::delete('/institutions/{institution}',   [InstitutionsController::class, 'destroy'])->name('institutions.destroy');

    Route::get('/faculties',                       [FacultiesController::class, 'index'])->name('faculties.index');
    Route::post('/faculties',                      [FacultiesController::class, 'store'])->name('faculties.store');
    Route::get('/faculties/{faculty}',             [FacultiesController::class, 'show'])->name('faculties.show');
    Route::put('/faculties/{faculty}',             [FacultiesController::class, 'update'])->name('faculties.update');
    Route::delete('/faculties/{faculty}',          [FacultiesController::class, 'destroy'])->name('faculties.destroy');

    Route::get('/careers',                         [CareersController::class, 'index'])->name('careers.index');
    Route::post('/careers',                        [CareersController::class, 'store'])->name('careers.store');
    Route::get('/careers/{career}',                [CareersController::class, 'show'])->name('careers.show');
    Route::put('/careers/{career}',                [CareersController::class, 'update'])->name('careers.update');
    Route::delete('/careers/{career}',             [CareersController::class, 'destroy'])->name('careers.destroy');

    Route::get('/semesters',                       [SemestersController::class, 'index'])->name('semesters.index');
    Route::post('/semesters',                      [SemestersController::class, 'store'])->name('semesters.store');
    Route::get('/semesters/{semester}',            [SemestersController::class, 'show'])->name('semesters.show');
    Route::put('/semesters/{semester}',            [SemestersController::class, 'update'])->name('semesters.update');
    Route::delete('/semesters/{semester}',         [SemestersController::class, 'destroy'])->name('semesters.destroy');

    Route::get('/curricula/manage',                [CurriculaController::class, 'manage'])->name('curricula.manage');
    Route::get('/curricula',                       [CurriculaController::class, 'index'])->name('curricula.index');
    Route::post('/curricula',                      [CurriculaController::class, 'store'])->name('curricula.store');
    Route::get('/curricula/{curriculum}',          [CurriculaController::class, 'show'])->name('curricula.show');
    Route::put('/curricula/{curriculum}',          [CurriculaController::class, 'update'])->name('curricula.update');
    Route::delete('/curricula/{curriculum}',       [CurriculaController::class, 'destroy'])->name('curricula.destroy');

    Route::get('/subjects',                        [SubjectsController::class, 'index'])->name('subjects.index');
    Route::post('/subjects',                       [SubjectsController::class, 'store'])->name('subjects.store');
    Route::get('/subjects/{subject}',              [SubjectsController::class, 'show'])->name('subjects.show');
    Route::put('/subjects/{subject}',              [SubjectsController::class, 'update'])->name('subjects.update');
    Route::delete('/subjects/{subject}',           [SubjectsController::class, 'destroy'])->name('subjects.destroy');
    Route::post('/subjects/{subject}/prerequisites',                       [SubjectsController::class, 'attachPrerequisite'])->name('subjects.prerequisites.attach');
    Route::delete('/subjects/{subject}/prerequisites/{prerequisite}',      [SubjectsController::class, 'detachPrerequisite'])->name('subjects.prerequisites.detach');
});