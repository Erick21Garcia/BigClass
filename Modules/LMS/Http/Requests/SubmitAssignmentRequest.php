<?php

namespace Modules\LMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Academic\Models\EnrollmentItem;

class SubmitAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $assignment = $this->route('assignment');
        $user = $this->user();

        if (! $user || ! $assignment) {
            return false;
        }

        $section = $assignment->unit->virtualCourse->section;

        // El estudiante autenticado debe estar matriculado en la Section
        // de la que cuelga esta tarea.
        return EnrollmentItem::where('section_id', $section->id)
            ->whereHas('enrollment.student.person', fn ($q) => $q->where('user_id', $user->id))
            ->exists();
    }

    public function rules(): array
    {
        return [
            'files'   => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'max:20480'], // 20MB por archivo
        ];
    }
}