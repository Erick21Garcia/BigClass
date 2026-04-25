<?php

namespace Modules\Institucion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Career extends Model
{
    use HasFactory;

    protected $table = 'careers';

    protected $fillable = [
        'faculty_id',
        'name',
        'code',
        'description',
        'modality',
        'duration_semesters',
        'title_awarded',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    public function semesters()
    {
        return $this->hasMany(Semester::class);
    }

    public function institution()
    {
        return $this->hasOneThrough(
            Institution::class,
            Faculty::class,
            'id',
            'id',
            'faculty_id',
            'institution_id'
        );
    }
}