<?php

use Illuminate\Support\Facades\Route;
use Modules\Academic\Http\Controllers\AcademicPeriodController;
use Modules\Academic\Http\Controllers\EnrollmentController;
use Modules\Academic\Http\Controllers\EvaluationParameterController;
use Modules\Academic\Http\Controllers\GradeController;
use Modules\Academic\Http\Controllers\SectionController;
use Modules\Academic\Http\Controllers\ScheduleController;

Route::middleware(['auth', 'verified'])->group(function () {

    // Períodos académicos
    Route::get('/academic-periods',                        [AcademicPeriodController::class, 'index'])->name('academic-periods.index');
    Route::get('/academic-periods/{academic_period}/parameters', [AcademicPeriodController::class, 'parameters'])->name('academic-periods.parameters');
    Route::get('/academic-periods/{academic_period}',      [AcademicPeriodController::class, 'show'])->name('academic-periods.show');
    Route::post('/academic-periods',                       [AcademicPeriodController::class, 'store'])->name('academic-periods.store');
    Route::get('/academic-periods/{academic_period}/edit', [AcademicPeriodController::class, 'edit'])->name('academic-periods.edit');
    Route::put('/academic-periods/{academic_period}',      [AcademicPeriodController::class, 'update'])->name('academic-periods.update');
    Route::delete('/academic-periods/{academic_period}',   [AcademicPeriodController::class, 'destroy'])->name('academic-periods.destroy');

    // Parámetros de evaluación (viven dentro del show de academic-period)
    Route::post('/evaluation-parameters',                              [EvaluationParameterController::class, 'store'])->name('evaluation-parameters.store');
    Route::put('/evaluation-parameters/{evaluation_parameter}',        [EvaluationParameterController::class, 'update'])->name('evaluation-parameters.update');
    Route::delete('/evaluation-parameters/{evaluation_parameter}',     [EvaluationParameterController::class, 'destroy'])->name('evaluation-parameters.destroy');

    // Notas — vista por materia
    Route::get('/grades',                                  [GradeController::class, 'index'])->name('grades.index');
    Route::post('/grades',                                 [GradeController::class, 'store'])->name('grades.store');
    Route::delete('/grades/{grade}',                       [GradeController::class, 'destroy'])->name('grades.destroy');

    // Matrículas
    Route::get('/enrollments',                             [EnrollmentController::class, 'index'])->name('enrollments.index');
    Route::get('/enrollments/create',                      [EnrollmentController::class, 'create'])->name('enrollments.create');
    Route::post('/enrollments',                            [EnrollmentController::class, 'store'])->name('enrollments.store');
    Route::get('/enrollments/{enrollment}',                [EnrollmentController::class, 'show'])->name('enrollments.show');
    Route::get('/enrollments/{enrollment}/grades',         [EnrollmentController::class, 'grades'])->name('enrollments.grades');
    Route::get('/enrollments/{enrollment}/edit',           [EnrollmentController::class, 'edit'])->name('enrollments.edit');
    Route::put('/enrollments/{enrollment}',                [EnrollmentController::class, 'update'])->name('enrollments.update');
    Route::delete('/enrollments/{enrollment}',             [EnrollmentController::class, 'destroy'])->name('enrollments.destroy');

    // Secciones
    Route::post('/sections',                               [SectionController::class, 'store'])->name('sections.store');
    Route::put('/sections/{section}',                      [SectionController::class, 'update'])->name('sections.update');
    Route::delete('/sections/{section}',                   [SectionController::class, 'destroy'])->name('sections.destroy');

    // Horarios
    Route::get('/schedules',                               [ScheduleController::class, 'index'])->name('schedules.index');
    Route::get('/schedules/events',                        [ScheduleController::class, 'events'])->name('schedules.events');
    Route::get('/schedules/panel',                         [ScheduleController::class, 'panel'])->name('schedules.panel');
    Route::post('/schedules',                              [ScheduleController::class, 'store'])->name('schedules.store');
    Route::put('/schedules/{schedule}',                    [ScheduleController::class, 'update'])->name('schedules.update');
    Route::delete('/schedules/{schedule}',                 [ScheduleController::class, 'destroy'])->name('schedules.destroy');

});