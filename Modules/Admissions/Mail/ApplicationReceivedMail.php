<?php

namespace Modules\Admissions\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Admissions\Models\Applicant;

class ApplicationReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Applicant $applicant
    ) {}

    public function build(): self
    {
        return $this
            ->subject('Hemos recibido tu postulación')
            ->view('admissions::emails.application-received', [
                'applicantName' => $this->applicant->full_name,
                'careerName'    => $this->applicant->career->name,
            ]);
    }
}