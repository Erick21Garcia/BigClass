<?php

namespace Modules\Institucion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subject extends Model
{
    use HasFactory;

    protected $table = 'subjects';

    protected $fillable = [
        'code',
        'name',
        'description',
        'credits',
        'active'
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function prerequisiteSubjects()
    {
        return $this->belongsToMany(
            Subject::class,
            'subject_prerequisites',
            'subject_id',
            'prerequisite_id'
        )->where('subject_prerequisites.active', true);
    }

    public function isPrerequisiteOf()
    {
        return $this->belongsToMany(
            Subject::class,
            'subject_prerequisites',
            'prerequisite_id',
            'subject_id'
        )->where('subject_prerequisites.active', true);
    }
}