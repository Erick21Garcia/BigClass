<?php

namespace Modules\Admissions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

class ApplicantDocument extends Model
{
    protected $table = 'applicant_documents';

    protected $fillable = [
        'applicant_id',
        'type',
        'file_path',
        'original_name',
        'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public const TYPES = ['cedula', 'titulo_bachiller', 'foto', 'comprobante_pago'];

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    /**
     * Mismo patrón que Resource del LMS: URL firmada, no ruta directa
     * (el disk de documentos de admisiones también es privado).
     */
    public function downloadUrl(int $minutes = 5): string
    {
        return URL::temporarySignedRoute(
            'admissions.documents.download',
            now()->addMinutes($minutes),
            ['document' => $this->id]
        );
    }
}