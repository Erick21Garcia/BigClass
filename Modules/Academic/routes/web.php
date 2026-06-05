<?php

use Illuminate\Support\Facades\Route;
use Modules\Academic\Http\Controllers\AcademicPeriodController;
use Modules\Academic\Http\Controllers\ActivityLogController;
use Modules\Academic\Http\Controllers\AttendanceController;
use Modules\Academic\Http\Controllers\EnrollmentController;
use Modules\Academic\Http\Controllers\EvaluationParameterController;
use Modules\Academic\Http\Controllers\ExportController;
use Modules\Academic\Http\Controllers\GradeController;
use Modules\Academic\Http\Controllers\ReportController;
use Modules\Academic\Http\Controllers\ScheduleController;
use Modules\Academic\Http\Controllers\SectionController;

Route::middleware(['auth', 'verified'])->group(function () {

    // ── Períodos académicos ───────────────────────────────────────────────────
    Route::get('/academic-periods',                              [AcademicPeriodController::class, 'index'])->name('academic-periods.index');
    Route::get('/academic-periods/{academic_period}/parameters', [AcademicPeriodController::class, 'parameters'])->name('academic-periods.parameters');
    Route::get('/academic-periods/{academic_period}',            [AcademicPeriodController::class, 'show'])->name('academic-periods.show');
    Route::post('/academic-periods',                             [AcademicPeriodController::class, 'store'])->name('academic-periods.store');
    Route::get('/academic-periods/{academic_period}/edit',       [AcademicPeriodController::class, 'edit'])->name('academic-periods.edit');
    Route::put('/academic-periods/{academic_period}',            [AcademicPeriodController::class, 'update'])->name('academic-periods.update');
    Route::delete('/academic-periods/{academic_period}',         [AcademicPeriodController::class, 'destroy'])->name('academic-periods.destroy');

    // ── Parámetros de evaluación ──────────────────────────────────────────────
    Route::post('/evaluation-parameters',                        [EvaluationParameterController::class, 'store'])->name('evaluation-parameters.store');
    Route::put('/evaluation-parameters/{evaluation_parameter}',  [EvaluationParameterController::class, 'update'])->name('evaluation-parameters.update');
    Route::delete('/evaluation-parameters/{evaluation_parameter}',[EvaluationParameterController::class, 'destroy'])->name('evaluation-parameters.destroy');

    // ── Notas ─────────────────────────────────────────────────────────────────
    Route::get('/grades',                                        [GradeController::class, 'index'])->name('grades.index');
    Route::post('/grades',                                       [GradeController::class, 'store'])->name('grades.store');
    Route::delete('/grades/{grade}',                             [GradeController::class, 'destroy'])->name('grades.destroy');

    // ── Matrículas ────────────────────────────────────────────────────────────
    Route::get('/enrollments',                                   [EnrollmentController::class, 'index'])->name('enrollments.index');
    Route::get('/enrollments/create',                            [EnrollmentController::class, 'create'])->name('enrollments.create');

    // ── Matrícula masiva ────────────────────────────────────────────────────────────
    Route::get('/enrollments/bulk-template',                     [EnrollmentController::class, 'bulkTemplate'])->name('enrollments.bulk-template');
    Route::post('/enrollments/bulk-preview',                     [EnrollmentController::class, 'bulkPreview'])->name('enrollments.bulk-preview');
    Route::post('/enrollments/bulk-store',                       [EnrollmentController::class, 'bulkStore'])->name('enrollments.bulk-store');

    // ── Matrículas ────────────────────────────────────────────────────────────
    Route::post('/enrollments',                                  [EnrollmentController::class, 'store'])->name('enrollments.store');
    Route::get('/enrollments/{enrollment}',                      [EnrollmentController::class, 'show'])->name('enrollments.show');
    Route::get('/enrollments/{enrollment}/grades',               [EnrollmentController::class, 'grades'])->name('enrollments.grades');
    Route::get('/enrollments/{enrollment}/edit',                 [EnrollmentController::class, 'edit'])->name('enrollments.edit');
    Route::put('/enrollments/{enrollment}',                      [EnrollmentController::class, 'update'])->name('enrollments.update');
    Route::delete('/enrollments/{enrollment}',                   [EnrollmentController::class, 'destroy'])->name('enrollments.destroy');

    // ── Secciones ─────────────────────────────────────────────────────────────
    Route::post('/sections',                                     [SectionController::class, 'store'])->name('sections.store');
    Route::put('/sections/{section}',                            [SectionController::class, 'update'])->name('sections.update');
    Route::delete('/sections/{section}',                         [SectionController::class, 'destroy'])->name('sections.destroy');

    // ── Horarios ──────────────────────────────────────────────────────────────
    Route::get('/schedules',                                     [ScheduleController::class, 'index'])->name('schedules.index');
    Route::get('/schedules/events',                              [ScheduleController::class, 'events'])->name('schedules.events');
    Route::get('/schedules/panel',                               [ScheduleController::class, 'panel'])->name('schedules.panel');
    Route::post('/schedules',                                    [ScheduleController::class, 'store'])->name('schedules.store');
    Route::put('/schedules/{schedule}',                          [ScheduleController::class, 'update'])->name('schedules.update');
    Route::delete('/schedules/{schedule}',                       [ScheduleController::class, 'destroy'])->name('schedules.destroy');

    // ── Asistencia ────────────────────────────────────────────────────────────
    Route::get('/sections/{section}/attendance',                 [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/sections/{section}/attendance',                [AttendanceController::class, 'store'])->name('attendance.store');
    Route::get('/sections/{section}/attendance/sheet',           [AttendanceController::class, 'sheet'])->name('attendance.sheet');
    Route::patch('/attendances/{attendance}/justify',            [AttendanceController::class, 'justify'])->name('attendances.justify');

    // ── Reportes PDF ──────────────────────────────────────────────────────────
    Route::get('reports/enrollment/{enrollment}/card',           [ReportController::class, 'enrollmentCard'])->name('reports.enrollment-card');
    Route::get('reports/enrollment/{enrollment}/grades',         [ReportController::class, 'gradeReport'])->name('reports.grade-report');
    Route::get('reports/subject-act',                            [ReportController::class, 'subjectAct'])->name('reports.subject-act');
    Route::get('reports/student/{student}/kardex',               [ReportController::class, 'kardex'])->name('reports.kardex');

    // ── Exportaciones Excel ────
    Route::get('exports/students-by-section',                 [ExportController::class, 'studentsBySection'])->name('exports.students-by-section');
    Route::get('exports/grade-report',                        [ExportController::class, 'gradeReport'])->name('exports.grade-report');
    Route::get('exports/kardex-by-career',                    [ExportController::class, 'kardexByCareer'])->name('exports.kardex-by-career');
    Route::get('exports/enrollments-by-period',               [ExportController::class, 'enrollmentsByPeriod'])->name('exports.enrollments-by-period');
    Route::get('exports/schedule-by-teacher',                 [ExportController::class, 'scheduleByTeacher'])->name('exports.schedule-by-teacher');
    Route::get('exports/attendance-report',                   [ExportController::class, 'attendanceReport'])->name('exports.attendance-report');
    Route::get('exports/approval-stats',                      [ExportController::class, 'approvalStats'])->name('exports.approval-stats');

    // ── Log de auditoría ──────────────────────────────────────────────────────
    Route::get('/activity-log',                                  [ActivityLogController::class, 'index'])->name('activity-log.index');

});