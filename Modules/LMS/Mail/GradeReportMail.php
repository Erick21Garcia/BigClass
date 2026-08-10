<?php

namespace Modules\LMS\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Academic\Models\Enrollment;

class GradeReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Enrollment $enrollment,
        public readonly string $pdfContent,
        public readonly string $filename,
    ) {}

    public function build(): self
    {
        return $this
            ->subject("Notas finales — {$this->enrollment->academicPeriod->name}")
            ->view('lms::emails.grade-report')
            ->with([
                'studentName' => $this->enrollment->student->person->full_name,
                'periodName'  => $this->enrollment->academicPeriod->name,
            ])
            ->attachData($this->pdfContent, $this->filename, [
                'mime' => 'application/pdf',
            ]);
    }
}