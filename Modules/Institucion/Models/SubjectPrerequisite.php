<?php

namespace Modules\Institucion\Entities;

use Illuminate\Database\Eloquent\Model;

class SubjectPrerequisite extends Model
{
    protected $table = 'subject_prerequisites';

    public $timestamps = false;

    protected $fillable = [
        'subject_id',
        'prerequisite_id',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function prerequisite()
    {
        return $this->belongsTo(Subject::class, 'prerequisite_id');
    }
}