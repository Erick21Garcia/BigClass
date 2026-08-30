<?php

namespace Modules\LMS\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Modules\LMS\Http\Requests\AddQuizQuestionRequest;
use Modules\LMS\Http\Requests\StoreQuizRequest;
use Modules\LMS\Models\Quiz;
use Modules\LMS\Models\QuizQuestion;
use Modules\LMS\Models\Unit;
use Modules\LMS\Services\QuizService;

class QuizController extends Controller
{
    public function __construct(
        private readonly QuizService $quizService
    ) {}

    public function store(StoreQuizRequest $request, Unit $unit): RedirectResponse
    {
        $this->quizService->create($unit, $request->validated());

        return back()->with('success', 'Cuestionario creado correctamente.');
    }

    public function update(StoreQuizRequest $request, Quiz $quiz): RedirectResponse
    {
        $this->quizService->update($quiz, $request->validated());

        return back()->with('success', 'Cuestionario actualizado.');
    }

    public function destroy(Quiz $quiz): RedirectResponse
    {
        $this->quizService->deactivate($quiz);

        return back()->with('success', 'Cuestionario eliminado.');
    }

    public function addQuestion(AddQuizQuestionRequest $request, Quiz $quiz): RedirectResponse
    {
        $this->quizService->addQuestion($quiz, $request->validated());

        return back()->with('success', 'Pregunta agregada.');
    }

    public function deleteQuestion(QuizQuestion $question): RedirectResponse
    {
        $this->quizService->deleteQuestion($question);

        return back()->with('success', 'Pregunta eliminada.');
    }

    /**
     * Editor de cuestionario — Punto 2 del backlog docente. Pantalla
     * dedicada aparte (no inline), por la densidad de armar preguntas.
     */
    public function edit(Quiz $quiz): \Inertia\Response
    {
        $quiz->load('questions.options', 'unit.virtualCourse');

        return \Inertia\Inertia::render('lms/teacher/QuizEditor', [
            'quiz' => [
                'id'           => $quiz->id,
                'title'        => $quiz->title,
                'max_attempts' => $quiz->max_attempts,
            ],
            'virtual_course_id' => $quiz->unit->virtualCourse->id,
            'questions' => $quiz->questions->map(fn ($q) => [
                'id'       => $q->id,
                'type'     => $q->type,
                'question' => $q->question,
                'points'   => $q->points,
                'options'  => $q->options->map(fn ($o) => [
                    'id'          => $o->id,
                    'option_text' => $o->option_text,
                    'is_correct'  => $o->is_correct,
                ]),
            ]),
        ]);
    }
}