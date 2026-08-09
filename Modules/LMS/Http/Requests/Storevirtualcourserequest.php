<?php

namespace Modules\LMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\LMS\Http\Requests\Concerns\AuthorizesTeacherOrAdmin;

class StoreVirtualCourseRequest extends FormRequest
{
    use AuthorizesTeacherOrAdmin;

    public function authorize(): bool
    {
        return $this->userCanManageSection($this->route('section'));
    }

    public function rules(): array
    {
        return [
            'is_published' => ['sometimes', 'boolean'],
            'syllabus'     => ['nullable', 'string'],
        ];
    }
}