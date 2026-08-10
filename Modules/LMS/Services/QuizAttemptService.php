<?php

namespace Modules\LMS\Services;

use Modules\LMS\Models\Quiz;
use Modules\LMS\Models\QuizAttempt;
use Modules\People\Models\Student;

class QuizAttemptService
{
    public function __construct(
        private readonly GradeSyncService $gradeSyncService,
    ) {}

    public function startAttempt(Quiz $quiz, Student $student): QuizAttempt
    {
        $attemptsUsed = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('student_id', $student->id)
            ->count();

        if ($attemptsUsed >= $quiz->max_attempts) {
            throw new \RuntimeException(
                "Ya usaste tus {$quiz->max_attempts} intento(s) permitido(s) para este cuestionario."
            );
        }

        return QuizAttempt::create([
            'quiz_id'        => $quiz->id,
            'student_id'     => $student->id,
            'attempt_number' => $attemptsUsed + 1,
            'started_at'     => now(),
            'status'         => 'in_progress',
        ]);
    }

    /**
     * $answers: array indexado por quiz_question_id, cada valor es
     * ['selected_option_id' => int] (opción múltiple) o
     * ['written_answer' => string] (ensayo).
     */
    public function submitAttempt(QuizAttempt $attempt, array $answers): QuizAttempt
    {
        $quiz = $attempt->quiz()->with('questions.options')->first();
        $autoScore = 0;

        foreach ($quiz->questions as $question) {
            $answerData = $answers[$question->id] ?? null;

            if ($question->type === 'multiple_choice') {
                $selectedOptionId = $answerData['selected_option_id'] ?? null;
                $correctOption = $question->correctOption();
                $isCorrect = $correctOption && $selectedOptionId == $correctOption->id;
                $pointsAwarded = $isCorrect ? $question->points : 0;
                $autoScore += $pointsAwarded;

                $attempt->answers()->create([
                    'quiz_question_id'    => $question->id,
                    'selected_option_id'  => $selectedOptionId,
                    'points_awarded'      => $pointsAwarded,
                ]);
            } else {
                // essay: sin puntos todavía, requiere revisión del docente.
                $attempt->answers()->create([
                    'quiz_question_id' => $question->id,
                    'written_answer'   => $answerData['written_answer'] ?? null,
                    'points_awarded'   => null,
                ]);
            }
        }

        $attempt->auto_score = $autoScore;
        $attempt->submitted_at = now();

        if ($quiz->is_fully_auto_gradable) {
            $attempt->manual_score = 0;
            $attempt->final_score = $autoScore;
            $attempt->status = 'graded';
            $attempt->graded_at = now();
            // graded_by queda null: lo calificó el sistema, no un docente.
        } else {
            $attempt->status = 'pending_review';
        }

        $attempt->save();

        if ($attempt->status === 'graded') {
            $this->syncBestAttempt($quiz, $attempt->student);
        }

        return $attempt->fresh('answers');
    }

    /**
     * $pointsByQuestionId: array [quiz_question_id => puntos otorgados],
     * solo para las preguntas de tipo 'essay' de este intento.
     */
    public function gradeEssayAnswers(QuizAttempt $attempt, array $pointsByQuestionId, int $gradedByUserId): QuizAttempt
    {
        $manualScore = 0;

        foreach ($attempt->answers as $answer) {
            if (array_key_exists($answer->quiz_question_id, $pointsByQuestionId)) {
                $points = (float) $pointsByQuestionId[$answer->quiz_question_id];
                $answer->update(['points_awarded' => $points]);
                $manualScore += $points;
            }
            // Las respuestas de opción múltiple ya tienen points_awarded
            // desde submitAttempt() — no se tocan aquí, ya están contadas
            // en $attempt->auto_score.
        }

        $attempt->manual_score = $manualScore;
        $attempt->final_score = (float) $attempt->auto_score + $manualScore;
        $attempt->status = 'graded';
        $attempt->graded_at = now();
        $attempt->graded_by = $gradedByUserId;
        $attempt->save();

        $this->syncBestAttempt($attempt->quiz, $attempt->student);

        return $attempt;
    }

    /**
     * Decisión del Punto 6: con múltiples intentos, se sincroniza a Grade
     * el MEJOR intento (final_score más alto) entre todos los ya
     * calificados ('graded') del estudiante para este quiz.
     */
    private function syncBestAttempt(Quiz $quiz, Student $student): void
    {
        $bestAttempt = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('student_id', $student->id)
            ->graded()
            ->orderByDesc('final_score')
            ->first();

        if (! $bestAttempt) {
            return;
        }

        $section = $quiz->unit->virtualCourse->section;

        $this->gradeSyncService->syncScore(
            section: $section,
            studentId: $student->id,
            evaluationParameterId: $quiz->evaluation_parameter_id,
            score: (float) $bestAttempt->final_score,
            observations: "Mejor intento del cuestionario: intento #{$bestAttempt->attempt_number}",
        );
    }
}