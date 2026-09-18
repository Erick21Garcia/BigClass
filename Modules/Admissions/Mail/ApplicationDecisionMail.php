<?php

namespace Modules\Admissions\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Admissions\Models\Applicant;

class ApplicationDecisionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Applicant $applicant
    ) {}

    public function build(): self
    {
        $approved = $this->applicant->status === 'aprobada';

        return $this
            ->subject($approved ? '¡Felicidades! Tu postulación fue aprobada' : 'Resultado de tu postulación')
            ->view('admissions::emails.application-decision', [
                'applicantName' => $this->applicant->full_name,
                'approved'      => $approved,
                'notes'         => $this->applicant->decision_notes,
            ]);
    }
}