<?php

namespace Modules\Admissions\Services;

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
     * Mueve una tarjeta del Kanban de un estado a otro (Punto 4).
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

        $applicant->update([
            'status'         => $newStatus,
            'decision_notes' => $notes,
            'decided_at'     => $isDecision ? now() : null,
            'decided_by'     => $isDecision ? $staffUserId : null,
        ]);

        if ($isDecision) {
            Mail::to($applicant->user->email)->queue(new ApplicationDecisionMail($applicant));
        }

        if ($newStatus === 'aprobada') {
            // Si la conversión falla (ej: sin periodo activo, sin
            // Semestre 1 configurado), la excepción se propaga y el
            // controller la captura — pero el update() de arriba ya se
            // guardó. Es una decisión consciente: el staff SÍ aprobó,
            // el problema es de configuración del ISI, no de la
            // decisión de admisión en sí. Queda 'aprobada' pero sin
            // Student creado, visible para reintentarlo.
            $this->conversionService->convertToStudent($applicant);
        }

        return $applicant;
    }
}