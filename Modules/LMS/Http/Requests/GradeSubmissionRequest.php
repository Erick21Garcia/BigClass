<?php

namespace Modules\LMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\LMS\Http\Requests\Concerns\AuthorizesTeacherOrAdmin;

class GradeSubmissionRequest extends FormRequest
{
    use AuthorizesTeacherOrAdmin;

    public function authorize(): bool
    {
        $submission = $this->route('submission');
        $section = $submission?->assignment?->unit?->virtualCourse?->section;

        return $this->userCanManageSection($section);
    }

    public function rules(): array
    {
        return [
            'grade'    => ['required', 'numeric', 'min:0', 'max:10'],
            'feedback' => ['nullable', 'string'],
        ];
    }
}