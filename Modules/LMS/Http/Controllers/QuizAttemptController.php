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

        return redirect()->route('lms.quiz-attempts.show', $attempt);
    }

    /**
     * Pantalla de responder el cuestionario — pieza #4 pendiente, parte 2.
     * NUNCA se expone is_correct de las opciones aquí (solo en el
     * QuizController::edit del docente).
     */
    public function show(\Illuminate\Http\Request $request, QuizAttempt $attempt): \Inertia\Response
    {
        $student = \Modules\People\Models\Student::whereHas('person', fn ($q) => $q->where('user_id', $request->user()->id))
            ->firstOrFail();

        abort_unless($attempt->student_id === $student->id, 403);
        abort_unless($attempt->status === 'in_progress', 403, 'Este intento ya fue entregado.');

        $attempt->load('quiz.questions.options');

        return \Inertia\Inertia::render('lms/student/QuizAttempt', [
            'virtual_course_id' => $attempt->quiz->unit->virtualCourse->id,
            'attempt' => ['id' => $attempt->id],
            'quiz'    => ['id' => $attempt->quiz->id, 'title' => $attempt->quiz->title],
            'questions' => $attempt->quiz->questions->map(fn ($q) => [
                'id'       => $q->id,
                'type'     => $q->type,
                'question' => $q->question,
                'options'  => $q->type === 'multiple_choice'
                    ? $q->options->map(fn ($o) => ['id' => $o->id, 'option_text' => $o->option_text])
                    : [],
            ]),
        ]);
    }

    public function submit(SubmitQuizAttemptRequest $request, QuizAttempt $attempt): RedirectResponse
    {
        $this->quizAttemptService->submitAttempt($attempt, $request->validated('answers'));

        return redirect()
            ->route('lms.student.quiz', $attempt->quiz_id)
            ->with('success', 'Cuestionario entregado correctamente.');
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

        return redirect()
            ->route('lms.teacher.gradebook', $attempt->quiz->unit->virtualCourse->id)
            ->with('success', 'Cuestionario calificado.');
    }

    /**
     * Pantalla de calificación de ensayos — pieza #5 pendiente.
     * Solo muestra las preguntas de tipo 'essay' (las de opción múltiple
     * ya se autocalificaron).
     */
    public function gradeForm(QuizAttempt $attempt): \Inertia\Response
    {
        $attempt->load('student.person', 'quiz', 'answers.question');

        $essayAnswers = $attempt->answers->filter(fn ($a) => $a->question->type === 'essay');

        return \Inertia\Inertia::render('lms/teacher/GradeQuizAttempt', [
            'attempt' => [
                'id'           => $attempt->id,
                'student_name' => $attempt->student->person->full_name,
                'quiz_title'   => $attempt->quiz->title,
                'auto_score'   => $attempt->auto_score,
            ],
            'essay_answers' => $essayAnswers->map(fn ($a) => [
                'question_id'    => $a->quiz_question_id,
                'question'       => $a->question->question,
                'written_answer' => $a->written_answer,
                'max_points'     => $a->question->points,
            ])->values(),
        ]);
    }
}