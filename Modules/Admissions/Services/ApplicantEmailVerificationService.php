<?php

namespace Modules\Admissions\Services;

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Modules\Admissions\Mail\VerifyApplicantEmailMail;

class ApplicantEmailVerificationService
{
    public const LINK_VALID_HOURS = 24;

    public function send(User $user): void
    {
        $url = URL::temporarySignedRoute(
            'admissions.email.verify',
            now()->addHours(self::LINK_VALID_HOURS),
            ['user' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        Mail::to($user->email)->queue(new VerifyApplicantEmailMail($user->name, $url));
    }

    public function verify(User $user, string $hash): void
    {
        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            throw new \RuntimeException('El enlace de verificación no es válido.');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }
    }
}