<?php

namespace Modules\LMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\LMS\Http\Requests\Concerns\AuthorizesTeacherOrAdmin;

class UpdateVirtualCourseRequest extends FormRequest
{
    use AuthorizesTeacherOrAdmin;

    public function authorize(): bool
    {
        $virtualCourse = $this->route('virtualCourse');

        return $this->userCanManageSection($virtualCourse?->section);
    }

    public function rules(): array
    {
        return [
            'syllabus' => ['nullable', 'string'],
        ];
    }
}