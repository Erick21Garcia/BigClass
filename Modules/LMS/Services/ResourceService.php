<?php

namespace Modules\LMS\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\LMS\Models\Resource;
use Modules\LMS\Models\Unit;

class ResourceService
{
    private const DISK = 'lms_materials';

    public function upload(Unit $unit, UploadedFile $file, string $title): Resource
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $fileType = $extension === 'pdf' ? 'pdf' : 'docx';

        // Guarda con nombre generado (evita colisiones/caracteres raros),
        // organizado por unidad.
        $path = $file->store("units/{$unit->id}", self::DISK);

        $nextOrder = $unit->resources()->max('order') + 1;

        return Resource::create([
            'unit_id'       => $unit->id,
            'title'         => $title,
            'file_path'     => $path,
            'original_name' => $file->getClientOriginalName(),
            'file_type'     => $fileType,
            'order'         => $nextOrder,
            'active'        => true,
        ]);
    }

    public function delete(Resource $resource): void
    {
        Storage::disk(self::DISK)->delete($resource->file_path);
        $resource->delete();
    }

    /**
     * Respuesta de descarga — usada por el controller detrás de la ruta
     * firmada (ver Resource::downloadUrl()).
     */
    public function download(Resource $resource)
    {
        return Storage::disk(self::DISK)->download(
            $resource->file_path,
            $resource->original_name
        );
    }
}