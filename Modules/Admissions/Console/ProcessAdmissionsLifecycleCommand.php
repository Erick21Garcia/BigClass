<?php

namespace Modules\Admissions\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Modules\Admissions\Mail\AbandonmentReminderMail;
use Modules\Admissions\Models\Applicant;
use Modules\Admissions\Models\AdmissionsSetting;

class ProcessAdmissionsLifecycleCommand extends Command
{
    protected $signature = 'admissions:process-lifecycle';

    protected $description = 'Envía recordatorios, marca postulaciones abandonadas, y purga las vencidas (Punto 6).';

    private const REMINDER_LEAD_DAYS = 5;
    private const ABANDONABLE_STATUSES = ['borrador', 'recibida', 'en_revision'];

    public function handle(): void
    {
        $settings = AdmissionsSetting::current();

        $this->sendReminders($settings->abandon_after_days);
        $this->markAbandoned($settings->abandon_after_days);
        $this->purgeExpired($settings->retention_after_months);
    }

    private function sendReminders(int $abandonAfterDays): void
    {
        $reminderThreshold = now()->subDays($abandonAfterDays - self::REMINDER_LEAD_DAYS);

        Applicant::whereIn('status', self::ABANDONABLE_STATUSES)
            ->whereNull('reminder_sent_at')
            ->where('last_activity_at', '<=', $reminderThreshold)
            ->each(function (Applicant $applicant) {
                Mail::to($applicant->user->email)->queue(
                    new AbandonmentReminderMail($applicant, self::REMINDER_LEAD_DAYS)
                );
                $applicant->update(['reminder_sent_at' => now()]);
            });
    }

    private function markAbandoned(int $abandonAfterDays): void
    {
        $abandonThreshold = now()->subDays($abandonAfterDays);

        Applicant::whereIn('status', self::ABANDONABLE_STATUSES)
            ->where('last_activity_at', '<=', $abandonThreshold)
            ->update(['status' => 'abandonada']);
    }

    private function purgeExpired(int $retentionAfterMonths): void
    {
        $retentionThreshold = now()->subMonths($retentionAfterMonths);

        // Se elimina (no solo se anonimiza) — cascade borra las filas de
        // ApplicantDocument, pero eso NO borra los archivos físicos en
        // el disco, así que se hace explícito aquí antes de borrar. El
        // User asociado se conserva a propósito (podría tener otros
        // vínculos futuros), solo se limpia Applicant + sus documentos.
        Applicant::whereIn('status', ['rechazada', 'abandonada'])
            ->where('updated_at', '<=', $retentionThreshold)
            ->with('documents')
            ->get()
            ->each(function (Applicant $applicant) {
                foreach ($applicant->documents as $document) {
                    Storage::disk('admissions_documents')->delete($document->file_path);
                }
                $applicant->delete();
            });
    }
}