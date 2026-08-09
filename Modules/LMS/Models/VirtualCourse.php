<?php

namespace Modules\LMS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Modules\Academic\Models\Section;

class VirtualCourse extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $table = 'virtual_courses';

    protected $fillable = [
        'section_id',
        'is_published',
        'syllabus',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    // --- Accesos de conveniencia (delegan a Section, no duplican datos) ---
    // No son relaciones Eloquent: son atajos para no escribir
    // $course->section->teacher en cada vista/servicio.

    public function getTeacherAttribute()
    {
        return $this->section->teacher;
    }

    public function getSubjectAttribute()
    {
        return $this->section->curriculum->subject;
    }

    public function getAcademicPeriodAttribute()
    {
        return $this->section->academicPeriod;
    }

    public function getEnrolledStudentsAttribute()
    {
        return $this->section->enrollmentItems->pluck('enrollment.student')->filter();
    }

    public function units(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Unit::class)->ordered();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['section_id', 'is_published', 'syllabus'])
            ->logOnlyDirty();
    }
}