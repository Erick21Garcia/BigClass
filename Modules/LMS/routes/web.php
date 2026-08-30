<?php

use Illuminate\Support\Facades\Route;
use Modules\LMS\Http\Controllers\VirtualCourseController;
use Modules\LMS\Http\Controllers\UnitController;
use Modules\LMS\Http\Controllers\ResourceController;
use Modules\LMS\Http\Controllers\AssignmentController;
use Modules\LMS\Http\Controllers\QuizController;
use Modules\LMS\Http\Controllers\QuizAttemptController;
use Modules\LMS\Http\Controllers\ProgressController;
use Modules\LMS\Http\Controllers\StudentDashboardController;
use Modules\LMS\Http\Controllers\TeacherDashboardController;

Route::middleware(['auth', 'verified'])->group(function () {

    // --- Aula Virtual (VirtualCourse) — Punto 1 ---
    Route::post('/lms/sections/{section}/virtual-course', [VirtualCourseController::class, 'store'])
        ->name('lms.virtual-courses.store');

    Route::put('/lms/virtual-courses/{virtualCourse}', [VirtualCourseController::class, 'update'])
        ->name('lms.virtual-courses.update');

    Route::post('/lms/virtual-courses/{virtualCourse}/publish', [VirtualCourseController::class, 'publish'])
        ->name('lms.virtual-courses.publish');

    Route::post('/lms/virtual-courses/{virtualCourse}/unpublish', [VirtualCourseController::class, 'unpublish'])
        ->name('lms.virtual-courses.unpublish');

    // --- Unidades (Unit) — Punto 3 ---
    Route::post('/lms/virtual-courses/{virtualCourse}/units', [UnitController::class, 'store'])
        ->name('lms.units.store');

    Route::put('/lms/units/{unit}', [UnitController::class, 'update'])
        ->name('lms.units.update');

    Route::post('/lms/virtual-courses/{virtualCourse}/units/reorder', [UnitController::class, 'reorder'])
        ->name('lms.units.reorder');

    Route::delete('/lms/units/{unit}', [UnitController::class, 'destroy'])
        ->name('lms.units.destroy');

    // --- Materiales (Resource) — Punto 4 ---
    Route::post('/lms/units/{unit}/resources', [ResourceController::class, 'store'])
        ->name('lms.resources.store');

    Route::delete('/lms/resources/{resource}', [ResourceController::class, 'destroy'])
        ->name('lms.resources.destroy');

    // --- Tareas (Assignment) — Punto 5 ---
    Route::post('/lms/units/{unit}/assignments', [AssignmentController::class, 'store'])
        ->name('lms.assignments.store');

    Route::put('/lms/assignments/{assignment}', [AssignmentController::class, 'update'])
        ->name('lms.assignments.update');

    Route::delete('/lms/assignments/{assignment}', [AssignmentController::class, 'destroy'])
        ->name('lms.assignments.destroy');

    Route::post('/lms/assignments/{assignment}/submit', [AssignmentController::class, 'submit'])
        ->name('lms.assignments.submit');

    Route::post('/lms/submissions/{submission}/grade', [AssignmentController::class, 'grade'])
        ->name('lms.submissions.grade');

    Route::get('/lms/submissions/{submission}/calificar', [AssignmentController::class, 'gradeForm'])
        ->name('lms.submissions.grade-form');

    // --- Cuestionarios (Quiz) — Punto 6 ---
    Route::post('/lms/units/{unit}/quizzes', [QuizController::class, 'store'])
        ->name('lms.quizzes.store');

    Route::put('/lms/quizzes/{quiz}', [QuizController::class, 'update'])
        ->name('lms.quizzes.update');

    Route::delete('/lms/quizzes/{quiz}', [QuizController::class, 'destroy'])
        ->name('lms.quizzes.destroy');

    Route::post('/lms/quizzes/{quiz}/questions', [QuizController::class, 'addQuestion'])
        ->name('lms.quizzes.questions.store');

    Route::delete('/lms/quiz-questions/{question}', [QuizController::class, 'deleteQuestion'])
        ->name('lms.quizzes.questions.destroy');

    Route::get('/lms/quizzes/{quiz}/edit', [QuizController::class, 'edit'])
        ->name('lms.quizzes.edit');

    Route::post('/lms/quizzes/{quiz}/attempts', [QuizAttemptController::class, 'start'])
        ->name('lms.quiz-attempts.start');

    Route::get('/lms/quiz-attempts/{attempt}/resolver', [QuizAttemptController::class, 'show'])
        ->name('lms.quiz-attempts.show');

    Route::post('/lms/quiz-attempts/{attempt}/submit', [QuizAttemptController::class, 'submit'])
        ->name('lms.quiz-attempts.submit');

    Route::post('/lms/quiz-attempts/{attempt}/grade-essay', [QuizAttemptController::class, 'gradeEssay'])
        ->name('lms.quiz-attempts.grade-essay');

    Route::get('/lms/quiz-attempts/{attempt}/calificar', [QuizAttemptController::class, 'gradeForm'])
        ->name('lms.quiz-attempts.grade-form');

    // --- Progreso (ResourceView) — Punto 9 ---
    Route::post('/lms/resources/{resource}/mark-viewed', [ProgressController::class, 'markViewed'])
        ->name('lms.resources.mark-viewed');

    Route::get('/lms/virtual-courses/{virtualCourse}/progress', [ProgressController::class, 'show'])
        ->name('lms.virtual-courses.progress');

    // --- Dashboard de estudiante (Mis materias) ---
    Route::get('/lms/mis-materias', [StudentDashboardController::class, 'index'])
        ->name('lms.student.index');

    Route::get('/lms/mis-materias/{virtualCourse}', [StudentDashboardController::class, 'show'])
        ->name('lms.student.course-detail');

    Route::get('/lms/assignments/{assignment}/entrega', [StudentDashboardController::class, 'assignment'])
        ->name('lms.student.assignment');

    Route::get('/lms/quizzes/{quiz}/resolver', [StudentDashboardController::class, 'quiz'])
        ->name('lms.student.quiz');

    // --- Dashboard de docente ---
    Route::get('/lms/mis-materias-docente', [TeacherDashboardController::class, 'index'])
        ->name('lms.teacher.index');

    Route::get('/lms/virtual-courses/{virtualCourse}/gradebook', [TeacherDashboardController::class, 'gradebook'])
        ->name('lms.teacher.gradebook');

    Route::get('/lms/mis-materias-docente/{virtualCourse}', [TeacherDashboardController::class, 'manage'])
        ->name('lms.teacher.manage');
});

// Ruta firmada — fuera del grupo 'auth' porque la validación de acceso
// la hace la firma de la URL (Resource::downloadUrl()), no el middleware
// de sesión. Así funciona también si el link se comparte/abre en pestaña
// nueva sin depender de la cookie de sesión.
Route::get('/lms/resources/{resource}/download', [ResourceController::class, 'download'])
    ->name('lms.resources.download')
    ->middleware('signed');