<?php

namespace Modules\LMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\LMS\Http\Requests\Concerns\AuthorizesTeacherOrAdmin;

class ReorderUnitsRequest extends FormRequest
{
    use AuthorizesTeacherOrAdmin;

    public function authorize(): bool
    {
        return $this->userCanManageSection($this->route('virtualCourse')?->section);
    }

    public function rules(): array
    {
        return [
            'unit_ids'   => ['required', 'array'],
            'unit_ids.*' => ['integer', 'exists:units,id'],
        ];
    }
}