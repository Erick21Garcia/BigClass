<?php

namespace Modules\Admissions\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Admissions\Models\Applicant;

class AbandonmentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Applicant $applicant,
        public readonly int $daysRemaining,
    ) {}

    public function build(): self
    {
        return $this
            ->subject('Tu postulación está por cerrarse por inactividad')
            ->view('admissions::emails.abandonment-reminder', [
                'applicantName' => $this->applicant->full_name,
                'daysRemaining' => $this->daysRemaining,
            ]);
    }
}