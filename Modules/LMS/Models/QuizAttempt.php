<?php

namespace Modules\LMS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Modules\People\Models\Student;

class QuizAttempt extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $table = 'quiz_attempts';

    protected $fillable = [
        'quiz_id',
        'student_id',
        'attempt_number',
        'started_at',
        'submitted_at',
        'auto_score',
        'manual_score',
        'final_score',
        'status',
        'graded_at',
        'graded_by',
    ];

    protected $casts = [
        'attempt_number' => 'integer',
        'started_at'     => 'datetime',
        'submitted_at'   => 'datetime',
        'auto_score'     => 'decimal:2',
        'manual_score'   => 'decimal:2',
        'final_score'    => 'decimal:2',
        'graded_at'      => 'datetime',
    ];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class);
    }

    public function scopeGraded($query)
    {
        return $query->where('status', 'graded');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['quiz_id', 'student_id', 'attempt_number', 'status', 'final_score'])
            ->logOnlyDirty();
    }
}