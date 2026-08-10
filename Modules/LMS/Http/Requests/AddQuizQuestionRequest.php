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
            'options'                 => ['required_if:type,multiple_choice', 'array', 'min:2'],
            'options.*.option_text'   => ['required_with:options', 'string'],
            'options.*.is_correct'    => ['required_with:options', 'boolean'],
        ];
    }

    /**
     * Regla extra que no se puede expresar solo con 'rules()': al menos
     * una opción debe estar marcada como correcta si el tipo es opción
     * múltiple.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('type') === 'multiple_choice') {
                $hasCorrect = collect($this->input('options', []))
                    ->contains(fn ($opt) => (bool) ($opt['is_correct'] ?? false));

                if (! $hasCorrect) {
                    $validator->errors()->add('options', 'Debe marcar al menos una opción como correcta.');
                }
            }
        });
    }
}