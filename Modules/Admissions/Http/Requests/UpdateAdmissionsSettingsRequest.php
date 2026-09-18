<?php

namespace Modules\Admissions\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdmissionsSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole('admin', 'super-admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'abandon_after_days'     => ['required', 'integer', 'min:1', 'max:365'],
            'retention_after_months' => ['required', 'integer', 'min:1', 'max:60'],
        ];
    }
}