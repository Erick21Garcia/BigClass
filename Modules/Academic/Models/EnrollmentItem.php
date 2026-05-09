<?php

namespace Modules\Academic\Models;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Institucion\Models\Curriculum;

class EnrollmentItem extends Model
{
    use LogsActivity;

    use HasFactory;

    protected $table = 'enrollment_items';

    protected $fillable = [
        'enrollment_id',
        'curricula_id',
        'section_id',
        'status',
        'final_grade',
        'locked',
        'active',
    ];

    protected $casts = [
        'final_grade' => 'decimal:2',
        'locked'      => 'boolean',
        'active'      => 'boolean',
    ];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function curriculum()
    {
        return $this->belongsTo(Curriculum::class, 'curricula_id');
    }

    public function subject()
    {
        return $this->hasOneThrough(
            Subject::class,
            Curriculum::class,
            'id',
            'id',
            'curricula_id',
            'subject_id'
        );
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'final_grade', 'locked'])
            ->logOnlyDirty();
    }

}