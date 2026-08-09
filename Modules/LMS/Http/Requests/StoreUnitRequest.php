<?php

namespace Modules\LMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\LMS\Http\Requests\Concerns\AuthorizesTeacherOrAdmin;

class StoreUnitRequest extends FormRequest
{
    use AuthorizesTeacherOrAdmin;

    public function authorize(): bool
    {
        // Se usa tanto para store (route: {virtualCourse}) como para
        // update (route: {unit}) — se resuelve la Section por cualquiera
        // de los dos que venga presente en la ruta.
        $section = optional($this->route('virtualCourse'))->section
            ?? optional($this->route('unit'))->virtualCourse?->section;

        return $this->userCanManageSection($section);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}