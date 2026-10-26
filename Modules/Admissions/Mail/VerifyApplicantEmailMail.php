<?php

namespace Modules\Admissions\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Admissions\Services\ApplicantEmailVerificationService;

class VerifyApplicantEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $applicantName,
        public readonly string $verificationUrl,
    ) {}

    public function build(): self
    {
        return $this
            ->subject('Verifica tu correo electrónico')
            ->view('admissions::emails.verify-applicant-email', [
                'applicantName'   => $this->applicantName,
                'verificationUrl' => $this->verificationUrl,
                'validHours'      => ApplicantEmailVerificationService::LINK_VALID_HOURS,
            ]);
    }
}