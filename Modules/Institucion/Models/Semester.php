<?php

namespace Modules\Institucion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Semester extends Model
{
    use HasFactory;

    protected $table = 'semesters';

    protected $fillable = [
        'career_id',
        'number',
        'name',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function career()
    {
        return $this->belongsTo(Career::class);
    }

    public function enrollments()
    {
        return $this->hasMany(\Modules\Academic\Models\Enrollment::class);
    }
    
}