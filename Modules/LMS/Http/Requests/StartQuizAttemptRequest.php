<?php

namespace Modules\LMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Academic\Models\EnrollmentItem;

class StartQuizAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        $quiz = $this->route('quiz');
        $user = $this->user();

        if (! $user || ! $quiz) {
            return false;
        }

        $section = $quiz->unit->virtualCourse->section;

        return EnrollmentItem::where('section_id', $section->id)
            ->whereHas('enrollment.student.person', fn ($q) => $q->where('user_id', $user->id))
            ->exists();
    }

    public function rules(): array
    {
        return [];
    }
}