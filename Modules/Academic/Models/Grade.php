<?php

namespace Modules\Academic\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Grade extends Model
{

    use LogsActivity;

    protected $table = 'grades';

    protected $fillable = [
        'enrollment_item_id',
        'evaluation_parameter_id',
        'score',
        'observations',
        'locked',
        'locked_at',
        'locked_by',
        'active'
    ];

    protected $casts = [
        'score'     => 'decimal:2',
        'locked'    => 'boolean',
        'locked_at' => 'datetime',
        'active'    => 'boolean',
    ];

    public function enrollmentItem()
    {
        return $this->belongsTo(\Modules\Academic\Models\EnrollmentItem::class);
    }

    public function evaluationParameter()
    {
        return $this->belongsTo(EvaluationParameter::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['score', 'observations', 'active', 'locked'])
            ->logOnlyDirty();
    }
    
}