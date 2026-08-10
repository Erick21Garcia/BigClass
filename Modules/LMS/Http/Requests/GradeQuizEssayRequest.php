<?php

namespace Modules\LMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\LMS\Http\Requests\Concerns\AuthorizesTeacherOrAdmin;

class GradeQuizEssayRequest extends FormRequest
{
    use AuthorizesTeacherOrAdmin;

    public function authorize(): bool
    {
        $attempt = $this->route('attempt');
        $section = $attempt?->quiz?->unit?->virtualCourse?->section;

        return $this->userCanManageSection($section);
    }

    public function rules(): array
    {
        return [
            'points'   => ['required', 'array'], // [quiz_question_id => puntos]
            'points.*' => ['numeric', 'min:0'],
        ];
    }
}