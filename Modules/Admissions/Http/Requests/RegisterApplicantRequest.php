<?php

namespace Modules\Admissions\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Modules\Admissions\Rules\ValidTurnstileToken;

class RegisterApplicantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'      => ['required', 'confirmed', Password::defaults()],
            'career_id'     => ['required', 'integer', 'exists:careers,id'],
            'captcha_token' => ['required', new ValidTurnstileToken],
        ];
    }

    public function messages(): array
    {
        return [
            'captcha_token.required' => 'Completa la verificación anti-bots antes de continuar.',
        ];
    }
}