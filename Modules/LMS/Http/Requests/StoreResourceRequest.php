<?php

namespace Modules\LMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\LMS\Http\Requests\Concerns\AuthorizesTeacherOrAdmin;

class StoreResourceRequest extends FormRequest
{
    use AuthorizesTeacherOrAdmin;

    public function authorize(): bool
    {
        $unit = $this->route('unit');

        return $this->userCanManageSection($unit?->virtualCourse?->section);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'file'  => ['required', 'file', 'mimes:pdf,docx,doc', 'max:20480'], // 20MB
        ];
    }
}