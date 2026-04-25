<?php

namespace Modules\People\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\People\Models\Person;
use Modules\Institucion\Models\Institution;

class Admin extends Model
{
    use HasFactory;

    protected $fillable = [
        'person_id',
        'institution_id',
        'position',
        'active',
    ];

    protected $casts = [
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