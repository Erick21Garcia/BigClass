<?php

use Illuminate\Support\Facades\Route;
use Modules\LMS\Http\Controllers\VirtualCourseController;
use Modules\LMS\Http\Controllers\UnitController;
use Modules\LMS\Http\Controllers\ResourceController;
use Modules\LMS\Http\Controllers\AssignmentController;

Route::middleware(['auth', 'verified'])->group(function () {

    // ── Aula Virtual ───────────────────────────────────────────────────
    Route::post('/lms/sections/{section}/virtual-course',           [VirtualCourseController::class, 'store'])->name('lms.virtual-courses.store');
    Route::put('/lms/virtual-courses/{virtualCourse}',              [VirtualCourseController::class, 'update'])->name('lms.virtual-courses.update');
    Route::post('/lms/virtual-courses/{virtualCourse}/publish',     [VirtualCourseController::class, 'publish'])->name('lms.virtual-courses.publish');
    Route::post('/lms/virtual-courses/{virtualCourse}/unpublish',   [VirtualCourseController::class, 'unpublish'])->name('lms.virtual-courses.unpublish');

    // ── Unidades ───────────────────────────────────────────────────
    Route::post('/lms/virtual-courses/{virtualCourse}/units',           [UnitController::class, 'store'])->name('lms.units.store');
    Route::put('/lms/units/{unit}',                                     [UnitController::class, 'update'])->name('lms.units.update');
    Route::post('/lms/virtual-courses/{virtualCourse}/units/reorder',   [UnitController::class, 'reorder'])->name('lms.units.reorder');
    Route::delete('/lms/units/{unit}',                                  [UnitController::class, 'destroy'])->name('lms.units.destroy');

    // ── Materiales ───────────────────────────────────────────────────
    Route::post('/lms/units/{unit}/resources',      [ResourceController::class, 'store'])->name('lms.resources.store');
    Route::delete('/lms/resources/{resource}',      [ResourceController::class, 'destroy'])->name('lms.resources.destroy');

    // ── Tareas ───────────────────────────────────────────────────
    Route::post('/lms/units/{unit}/assignments',            [AssignmentController::class, 'store'])->name('lms.assignments.store');
    Route::put('/lms/assignments/{assignment}',             [AssignmentController::class, 'update'])->name('lms.assignments.update');
    Route::delete('/lms/assignments/{assignment}',          [AssignmentController::class, 'destroy'])->name('lms.assignments.destroy');
    Route::post('/lms/assignments/{assignment}/submit',     [AssignmentController::class, 'submit'])->name('lms.assignments.submit');
    Route::post('/lms/submissions/{submission}/grade',      [AssignmentController::class, 'grade'])->name('lms.submissions.grade');

    // ── Cuestionarios (Quiz) ───────────────────────────────────────────────────
    Route::post('/lms/units/{unit}/quizzes',                [QuizController::class, 'store'])->name('lms.quizzes.store');
    Route::put('/lms/quizzes/{quiz}',                       [QuizController::class, 'update'])->name('lms.quizzes.update');
    Route::delete('/lms/quizzes/{quiz}',                    [QuizController::class, 'destroy'])->name('lms.quizzes.destroy');
    Route::post('/lms/quizzes/{quiz}/questions',            [QuizController::class, 'addQuestion'])->name('lms.quizzes.questions.store');
    Route::delete('/lms/quiz-questions/{question}',         [QuizController::class, 'deleteQuestion'])->name('lms.quizzes.questions.destroy');
    Route::post('/lms/quizzes/{quiz}/attempts',             [QuizAttemptController::class, 'start'])->name('lms.quiz-attempts.start');
    Route::post('/lms/quiz-attempts/{attempt}/submit',      [QuizAttemptController::class, 'submit'])->name('lms.quiz-attempts.submit');
    Route::post('/lms/quiz-attempts/{attempt}/grade-essay', [QuizAttemptController::class, 'gradeEssay'])->name('lms.quiz-attempts.grade-essay');

    // ── Progreso (ResourceView) ───────────────────────────────────────────────────
    Route::post('/lms/resources/{resource}/mark-viewed', [ProgressController::class, 'markViewed'])->name('lms.resources.mark-viewed');
    Route::get('/lms/virtual-courses/{virtualCourse}/progress', [ProgressController::class, 'show'])->name('lms.virtual-courses.progress');

});

// Ruta firmada — fuera del grupo 'auth' porque la validación de acceso
// la hace la firma de la URL (Resource::downloadUrl()), no el middleware
// de sesión. Así funciona también si el link se comparte/abre en pestaña
// nueva sin depender de la cookie de sesión.
Route::get('/lms/resources/{resource}/download', [ResourceController::class, 'download'])->name('lms.resources.download')->middleware('signed');