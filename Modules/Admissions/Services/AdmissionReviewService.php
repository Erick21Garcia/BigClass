<?php

namespace Modules\Admissions\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Admissions\Mail\ApplicationDecisionMail;
use Modules\Admissions\Models\Applicant;

class AdmissionReviewService
{
    private const KANBAN_STATUSES = ['recibida', 'en_revision', 'aprobada', 'rechazada'];

    public function __construct(
        private readonly AdmissionConversionService $conversionService,
    ) {}

    /**
     * Mueve una tarjeta del Kanban de un estado a otro.
     */
    public function moveStatus(Applicant $applicant, string $newStatus, ?string $notes, int $staffUserId): Applicant
    {
        if (! in_array($newStatus, self::KANBAN_STATUSES, true)) {
            throw new \RuntimeException("Estado inválido: {$newStatus}.");
        }

        if (! in_array($applicant->status, self::KANBAN_STATUSES, true)) {
            throw new \RuntimeException('Esta postulación no está en el flujo de revisión (borrador o abandonada).');
        }

        $isDecision = in_array($newStatus, ['aprobada', 'rechazada'], true);

        DB::transaction(function () use ($applicant, $newStatus, $notes, $staffUserId, $isDecision) {
            $applicant->update([
                'status'         => $newStatus,
                'decision_notes' => $notes,
                'decided_at'     => $isDecision ? now() : null,
                'decided_by'     => $isDecision ? $staffUserId : null,
            ]);

            if ($newStatus === 'aprobada') {
                $this->conversionService->convertToStudent($applicant);
            }
        });

        if ($isDecision) {
            Mail::to($applicant->user->email)->queue(new ApplicationDecisionMail($applicant));
        }

        return $applicant;
    }
}