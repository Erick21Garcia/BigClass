<?php

namespace Modules\LMS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\People\Models\Student;

class ResourceView extends Model
{
    protected $table = 'resource_views';

    protected $fillable = [
        'resource_id',
        'student_id',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}