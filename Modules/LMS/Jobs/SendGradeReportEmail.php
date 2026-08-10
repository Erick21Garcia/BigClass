<?php

namespace Modules\LMS\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Modules\Academic\Models\Enrollment;
use Modules\Academic\Services\ReportService;
use Modules\LMS\Mail\GradeReportMail;

class SendGradeReportEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly Enrollment $enrollment
    ) {}

    public function handle(ReportService $reportService): void
    {
        $this->enrollment->loadMissing('student.person.user');

        $email = $this->enrollment->student->person->user?->email;

        if (! $email) {
            // Estudiante sin cuenta de usuario vinculada todavía — no hay
            // a dónde enviar. Se omite silenciosamente (no es un error del
            // cierre de periodo, es un dato faltante del estudiante).
            return;
        }

        $pdf = $reportService->generateGradeReport($this->enrollment);
        $filename = $reportService->gradeReportFilename($this->enrollment);

        Mail::to($email)->send(
            new GradeReportMail($this->enrollment, $pdf->output(), $filename)
        );
    }
}