<?php

namespace Modules\LMS\Services;

use Modules\LMS\Models\Quiz;
use Modules\LMS\Models\QuizQuestion;
use Modules\LMS\Models\Unit;

class QuizService
{
    public function create(Unit $unit, array $data): Quiz
    {
        return Quiz::create([
            'unit_id'                 => $unit->id,
            'evaluation_parameter_id' => $data['evaluation_parameter_id'],
            'title'                   => $data['title'],
            'description'             => $data['description'] ?? null,
            'max_attempts'            => $data['max_attempts'] ?? 1,
            'active'                  => true,
        ]);
    }

    public function update(Quiz $quiz, array $data): Quiz
    {
        $quiz->update([
            'title'                   => $data['title'],
            'description'             => $data['description'] ?? null,
            'max_attempts'            => $data['max_attempts'] ?? $quiz->max_attempts,
            'evaluation_parameter_id' => $data['evaluation_parameter_id'],
        ]);

        return $quiz;
    }

    public function deactivate(Quiz $quiz): Quiz
    {
        $quiz->update(['active' => false]);

        return $quiz;
    }

    /**
     * $data['type'] = 'multiple_choice' | 'essay'
     * $data['options'] = [['option_text' => ..., 'is_correct' => bool], ...]
     *   — requerido y usado solo si type = 'multiple_choice'.
     */
    public function addQuestion(Quiz $quiz, array $data): QuizQuestion
    {
        $nextOrder = $quiz->questions()->max('order') + 1;

        $question = $quiz->questions()->create([
            'type'     => $data['type'],
            'question' => $data['question'],
            'points'   => $data['points'],
            'order'    => $nextOrder,
        ]);

        if ($data['type'] === 'multiple_choice') {
            foreach ($data['options'] as $option) {
                $question->options()->create([
                    'option_text' => $option['option_text'],
                    'is_correct'  => (bool) $option['is_correct'],
                ]);
            }
        }

        return $question->load('options');
    }

    public function deleteQuestion(QuizQuestion $question): void
    {
        $question->delete(); // cascade borra options vía FK
    }
}