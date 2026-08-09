<?php

namespace Modules\LMS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Unit extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $table = 'units';

    protected $fillable = [
        'virtual_course_id',
        'name',
        'order',
        'active',
    ];

    protected $casts = [
        'order'  => 'integer',
        'active' => 'boolean',
    ];

    public function virtualCourse(): BelongsTo
    {
        return $this->belongsTo(VirtualCourse::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    public function resources(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Resource::class)->ordered();
    }

    public function assignments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    // NOTA: aquí se agregará hasMany(Quiz::class) cuando implementemos el Punto 6.

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['virtual_course_id', 'name', 'order', 'active'])
            ->logOnlyDirty();
    }
}