<?php

namespace Modules\LMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\LMS\Http\Requests\Concerns\AuthorizesTeacherOrAdmin;

class AddQuizQuestionRequest extends FormRequest
{
    use AuthorizesTeacherOrAdmin;

    public function authorize(): bool
    {
        $quiz = $this->route('quiz');

        return $this->userCanManageSection($quiz?->unit?->virtualCourse?->section);
    }

    public function rules(): array
    {
        return [
            'type'                    => ['required', 'in:multiple_choice,essay'],
            'question'                => ['required', 'string'],
            'points'                  => ['required', 'numeric', 'min:0.5'],
            'options'                 => ['array'],
            'options.*.option_text'   => ['required_with:options', 'string'],
            'options.*.is_correct'    => ['required_with:options', 'boolean'],
        ];
    }

    /**
     * El mínimo de 2 opciones y "al menos una correcta" solo aplican
     * cuando el tipo es opción múltiple — para 'essay' el frontend manda
     * options: [] a propósito, y eso debe pasar sin error.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('type') !== 'multiple_choice') {
                return;
            }

            $options = collect($this->input('options', []));

            if ($options->count() < 2) {
                $validator->errors()->add('options', 'Debe haber al menos 2 opciones para preguntas de opción múltiple.');
                return;
            }

            $hasCorrect = $options->contains(fn ($opt) => (bool) ($opt['is_correct'] ?? false));

            if (! $hasCorrect) {
                $validator->errors()->add('options', 'Debe marcar al menos una opción como correcta.');
            }
        });
    }
}