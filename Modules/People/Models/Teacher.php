<?php

namespace Modules\People\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\People\Models\Person;
use Modules\Institucion\Models\Institution;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'person_id',
        'institution_id',
        'hire_date',
        'academic_degree',
        'active',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'active' => 'boolean',
    ];

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }
}