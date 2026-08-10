<?php

namespace Modules\LMS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizQuestion extends Model
{
    protected $table = 'quiz_questions';

    protected $fillable = [
        'quiz_id',
        'type',
        'question',
        'points',
        'order',
    ];

    protected $casts = [
        'points' => 'decimal:2',
        'order'  => 'integer',
    ];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuizQuestionOption::class);
    }

    public function correctOption(): ?QuizQuestionOption
    {
        return $this->options->firstWhere('is_correct', true);
    }
}