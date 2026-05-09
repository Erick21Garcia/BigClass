<?php

namespace Modules\Academic\Models;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\People\Models\Student;
use Modules\Institucion\Models\Career;
use Modules\Institucion\Models\Semester;

class Enrollment extends Model
{

    use LogsActivity;

    use HasFactory;

    protected $table = 'enrollments';

    protected $fillable = [
        'student_id',
        'career_id',
        'semester_id',
        'academic_period_id',
        'enrollment_date',
        'type',
        'status',
    ];

    protected $casts = [
        'enrollment_date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function career()
    {
        return $this->belongsTo(Career::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function academicPeriod()
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function items()
    {
        return $this->hasMany(EnrollmentItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByPeriod($query, $periodId)
    {
        return $query->where('academic_period_id', $periodId);
    }

    public function getIsActiveAttribute()
    {
        return $this->status === 'active';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'type', 'enrollment_date', 'academic_period_id'])
            ->logOnlyDirty();
    }
    
}