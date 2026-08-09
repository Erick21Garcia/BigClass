<?php

namespace Modules\LMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\LMS\Http\Requests\Concerns\AuthorizesTeacherOrAdmin;

class StoreAssignmentRequest extends FormRequest
{
    use AuthorizesTeacherOrAdmin;

    public function authorize(): bool
    {
        // store usa route {unit}, update usa route {assignment}.
        $section = optional($this->route('unit'))->virtualCourse?->section
            ?? optional($this->route('assignment'))->unit?->virtualCourse?->section;

        return $this->userCanManageSection($section);
    }

    public function rules(): array
    {
        return [
            'title'                    => ['required', 'string', 'max:255'],
            'description'              => ['nullable', 'string'],
            'due_date'                 => ['required', 'date'],
            'evaluation_parameter_id'  => ['required', 'integer', 'exists:evaluation_parameters,id'],
        ];
    }
}