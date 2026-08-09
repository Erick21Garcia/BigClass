<?php

namespace Modules\LMS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Modules\Academic\Models\EvaluationParameter;

class Assignment extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $table = 'assignments';

    protected $fillable = [
        'unit_id',
        'evaluation_parameter_id',
        'title',
        'description',
        'due_date',
        'active',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'active'   => 'boolean',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function evaluationParameter(): BelongsTo
    {
        return $this->belongsTo(EvaluationParameter::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function getIsPastDueAttribute(): bool
    {
        return now()->greaterThan($this->due_date);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['unit_id', 'evaluation_parameter_id', 'title', 'due_date', 'active'])
            ->logOnlyDirty();
    }
}