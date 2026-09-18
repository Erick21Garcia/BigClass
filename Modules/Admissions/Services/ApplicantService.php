<?php

namespace Modules\Admissions\Services;

use Modules\Admissions\Mail\ApplicationReceivedMail;
use Illuminate\Support\Facades\Mail;
use Modules\Admissions\Models\Applicant;
use Modules\Admissions\Models\ApplicantDocument;

class ApplicantService
{
    private const PERSONAL_FIELDS = [
        'first_name', 'second_name', 'first_surname', 'second_surname',
        'identification_number', 'phone', 'cellphone', 'birthdate', 'place_birth',
        'main_street', 'secondary_street', 'neighborhood', 'reference',
        'marital_status_id', 'type_identification_id', 'sex_id', 'nationality_id',
        'education_level_id', 'countries_id', 'provinces_id', 'cities_id',
    ];

    public function updatePersonalData(Applicant $applicant, array $data): Applicant
    {
        $applicant->update([
            ...array_intersect_key($data, array_flip(self::PERSONAL_FIELDS)),
            'last_activity_at' => now(),
        ]);

        return $applicant;
    }

    /**
     * Envío final — Punto 2. Solo se puede enviar si están los 4
     * documentos Y los datos personales completos. Pasa de 'borrador' a
     * 'recibida' (entra al Kanban del Punto 4).
     */
    public function submit(Applicant $applicant): Applicant
    {
        if ($applicant->status !== 'borrador') {
            throw new \RuntimeException('Esta postulación ya fue enviada.');
        }

        $missingFields = collect(self::PERSONAL_FIELDS)
            ->filter(fn ($field) => blank($applicant->{$field}));

        if ($missingFields->isNotEmpty()) {
            throw new \RuntimeException('Faltan datos personales por completar antes de enviar.');
        }

        $uploadedTypes = $applicant->documents()->pluck('type');
        $missingDocs = collect(ApplicantDocument::TYPES)->diff($uploadedTypes);

        if ($missingDocs->isNotEmpty()) {
            throw new \RuntimeException('Faltan documentos por subir: ' . $missingDocs->implode(', '));
        }

        $applicant->update([
            'status'           => 'recibida',
            'submitted_at'     => now(),
            'last_activity_at' => now(),
        ]);

        Mail::to($applicant->user->email)->queue(new ApplicationReceivedMail($applicant));

        return $applicant;
    }
}