<?php

namespace Modules\LMS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Resource extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $table = 'resources';

    protected $fillable = [
        'unit_id',
        'title',
        'file_path',
        'original_name',
        'file_type',
        'order',
        'active',
    ];

    protected $casts = [
        'order'  => 'integer',
        'active' => 'boolean',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * URL firmada temporal (funciona igual con disk 'local' o 's3' — a
     * diferencia de Storage::temporaryUrl(), que el disk 'local' NO soporta).
     * Apunta a una ruta de la app que hace el streaming del archivo.
     */
    public function downloadUrl(int $minutes = 5): string
    {
        return \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'lms.resources.download',
            now()->addMinutes($minutes),
            ['resource' => $this->id]
        );
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['unit_id', 'title', 'file_type', 'active'])
            ->logOnlyDirty();
    }
}