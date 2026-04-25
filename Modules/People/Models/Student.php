<?php

namespace Modules\People\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'person_id',
        'enrollment_number',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function person()
    {
        return $this->belongsTo(Person::class);
    }
}