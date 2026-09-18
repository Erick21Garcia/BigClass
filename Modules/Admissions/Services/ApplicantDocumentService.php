<?php

namespace Modules\Admissions\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Admissions\Models\Applicant;
use Modules\Admissions\Models\ApplicantDocument;

class ApplicantDocumentService
{
    private const DISK = 'admissions_documents';

    /**
     * Subir/reemplazar un documento. Como la tabla tiene unique
     * (applicant_id, type), esto siempre actualiza si ya existía uno del
     * mismo tipo — no acumula versiones viejas.
     */
    public function upload(Applicant $applicant, string $type, UploadedFile $file): ApplicantDocument
    {
        $existing = ApplicantDocument::where('applicant_id', $applicant->id)
            ->where('type', $type)
            ->first();

        if ($existing) {
            Storage::disk(self::DISK)->delete($existing->file_path);
        }

        $path = $file->store("applicants/{$applicant->id}", self::DISK);

        $document = ApplicantDocument::updateOrCreate(
            ['applicant_id' => $applicant->id, 'type' => $type],
            [
                'file_path'     => $path,
                'original_name' => $file->getClientOriginalName(),
                'uploaded_at'   => now(),
            ]
        );

        $applicant->update(['last_activity_at' => now()]);

        return $document;
    }

    public function download(ApplicantDocument $document)
    {
        return Storage::disk(self::DISK)->download($document->file_path, $document->original_name);
    }
}