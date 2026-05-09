<?php

namespace Modules\Academic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Modules\People\Models\Student;
use App\Models\User;

class Attendance extends Model
{
    use LogsActivity;

    protected $fillable = [
        'section_id',
        'student_id',
        'schedule_id',
        'recorded_by',
        'date',
        'status',
        'justified',
        'justification_note',
    ];

    protected $casts = [
        'date'      => 'date',
        'justified' => 'boolean',
    ];

    const STATUS_PRESENT = 'present';
    const STATUS_ABSENT  = 'absent';
    const STATUS_LATE    = 'late';

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'justified', 'justification_note'])
            ->logOnlyDirty();
    }
}