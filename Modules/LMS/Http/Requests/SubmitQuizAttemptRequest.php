<?php

namespace Modules\LMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitQuizAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        // El intento (QuizAttempt) ya se creó con startAttempt() a nombre
        // del estudiante autenticado — aquí solo verificamos que el intento
        // que se está enviando le pertenece a él.
        $attempt = $this->route('attempt');
        $user = $this->user();

        if (! $user || ! $attempt) {
            return false;
        }

        return $attempt->student->person?->user_id === $user->id
            && $attempt->status === 'in_progress';
    }

    public function rules(): array
    {
        return [
            'answers'                          => ['required', 'array'],
            'answers.*.selected_option_id'     => ['nullable', 'integer', 'exists:quiz_question_options,id'],
            'answers.*.written_answer'         => ['nullable', 'string'],
        ];
    }
}