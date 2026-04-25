<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'active',
    ];

    // Relaciones
    public function provinces()
    {
        return $this->hasMany(Province::class);
    }

    public function nationalities()
    {
        return $this->hasMany(Nationality::class);
    }
    
}