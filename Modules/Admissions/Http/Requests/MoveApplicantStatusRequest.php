<?php

namespace Modules\Admissions\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoveApplicantStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole('admin', 'super-admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:recibida,en_revision,aprobada,rechazada'],
            'notes'  => ['nullable', 'string'],
        ];
    }
}