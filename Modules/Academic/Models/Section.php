<?php

namespace Modules\Academic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    protected $fillable = [
        'curricula_id', 
        'teacher_id', 
        'academic_period_id', 
        'name', 
        'quota', 
        'active'
    ];

    protected $casts = [
        'active' => 'boolean'
    ];

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(\Modules\Institucion\Models\Curriculum::class, 'curricula_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(\Modules\People\Models\Teacher::class, 'teacher_id');
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function enrollmentItems(): HasMany
    {
        return $this->hasMany(EnrollmentItem::class);
    }

}