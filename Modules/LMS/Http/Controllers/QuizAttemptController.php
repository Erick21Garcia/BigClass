<?php

namespace Modules\LMS\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Modules\LMS\Http\Requests\GradeQuizEssayRequest;
use Modules\LMS\Http\Requests\StartQuizAttemptRequest;
use Modules\LMS\Http\Requests\SubmitQuizAttemptRequest;
use Modules\LMS\Models\Quiz;
use Modules\LMS\Models\QuizAttempt;
use Modules\LMS\Services\QuizAttemptService;
use Modules\People\Models\Student;

class QuizAttemptController extends Controller
{
    public function __construct(
        private readonly QuizAttemptService $quizAttemptService
    ) {}

    public function start(StartQuizAttemptRequest $request, Quiz $quiz): RedirectResponse
    {
        $student = Student::whereHas('person', fn ($q) => $q->where('user_id', $request->user()->id))
            ->firstOrFail();

        try {
            $attempt = $this->quizAttemptService->startAttempt($quiz, $student);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['attempt' => $e->getMessage()]);
        }

        // NOTA: cuando construyamos el frontend, esto probablemente cambie
        // a un redirect()->route('lms.quiz-attempts.show', $attempt) hacia
        // la vista de "resolver cuestionario". Por ahora, sin esa ruta
        // todavía, se devuelve el ID del intento creado.
        return back()->with('success', 'Cuestionario iniciado.')->with('attempt_id', $attempt->id);
    }

    public function submit(SubmitQuizAttemptRequest $request, QuizAttempt $attempt): RedirectResponse
    {
        $this->quizAttemptService->submitAttempt($attempt, $request->validated('answers'));

        return back()->with('success', 'Cuestionario entregado correctamente.');
    }

    public function gradeEssay(GradeQuizEssayRequest $request, QuizAttempt $attempt): RedirectResponse
    {
        try {
            $this->quizAttemptService->gradeEssayAnswers(
                $attempt,
                $request->validated('points'),
                $request->user()->id
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['grade' => $e->getMessage()]);
        }

        return back()->with('success', 'Cuestionario calificado.');
    }
}