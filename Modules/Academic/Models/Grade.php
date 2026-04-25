<?php

namespace Modules\Academic\Models;

use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    protected $table = 'grades';

    protected $fillable = [
        'enrollment_item_id',
        'evaluation_parameter_id',
        'score',
        'observations',
        'active'
    ];

    protected $casts = [
        'score'  => 'decimal:2',
        'active' => 'boolean',
    ];

    public function enrollmentItem()
    {
        return $this->belongsTo(\Modules\Academic\Models\EnrollmentItem::class);
    }

    public function evaluationParameter()
    {
        return $this->belongsTo(EvaluationParameter::class);
    }
}