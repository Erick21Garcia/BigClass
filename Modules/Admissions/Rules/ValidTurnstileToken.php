<?php

namespace Modules\Admissions\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class ValidTurnstileToken implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secret = config('services.turnstile.secret_key');

        if (! $secret) {
            $fail('La verificación anti-bots no está configurada.');
            return;
        }

        try {
            $response = Http::asForm()->timeout(5)->post(
                'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                [
                    'secret'   => $secret,
                    'response' => $value,
                    'remoteip' => request()->ip(),
                ]
            );
        } catch (\Throwable $e) {
            report($e);
            $fail('No pudimos verificar que no eres un robot. Intenta de nuevo.');
            return;
        }

        if (! $response->json('success')) {
            $fail('No pudimos verificar que no eres un robot. Intenta de nuevo.');
        }
    }
}