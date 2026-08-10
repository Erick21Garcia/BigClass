<?php

namespace Modules\LMS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Modules\Academic\Models\EvaluationParameter;

class Quiz extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $table = 'quizzes';

    protected $fillable = [
        'unit_id',
        'evaluation_parameter_id',
        'title',
        'description',
        'max_attempts',
        'active',
    ];

    protected $casts = [
        'max_attempts' => 'integer',
        'active'       => 'boolean',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function evaluationParameter(): BelongsTo
    {
        return $this->belongsTo(EvaluationParameter::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /**
     * true si TODAS las preguntas son de opción múltiple — solo entonces
     * un intento puede pasar directo a 'graded' sin revisión del docente.
     */
    public function getIsFullyAutoGradableAttribute(): bool
    {
        return $this->questions->every(fn ($q) => $q->type === 'multiple_choice');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['unit_id', 'evaluation_parameter_id', 'title', 'max_attempts', 'active'])
            ->logOnlyDirty();
    }
}