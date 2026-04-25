<?php

namespace Modules\Institucion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Institution extends Model
{
    use HasFactory;

    protected $table = 'institutions';

    protected $fillable = [
        'name',
        'type',
        'code',
        'acronym',
        'email',
        'phone',
        'address',
        'city',
        'province',
        'country',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
    
    public function faculties()
    {
        return $this->hasMany(Faculty::class);
    }
}