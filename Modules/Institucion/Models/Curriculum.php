<?php

namespace Modules\Institucion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Curriculum extends Model
{
    use HasFactory;

    protected $table = 'curricula';

    protected $fillable = [
        'career_id',
        'subject_id',
        'semester_id',
        'is_mandatory',
        'active',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'active' => 'boolean',
    ];

    public function career()
    {
        return $this->belongsTo(Career::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function enrollmentItems()
    {
        return $this->hasMany(EnrollmentItem::class, 'curricula_id');
    }

    public function sections(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\Modules\Academic\Models\Section::class, 'curricula_id');
    }
    
}