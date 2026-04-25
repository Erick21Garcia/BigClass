<?php

namespace Modules\People\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Person extends Model
{
    use HasFactory;

    protected $table = 'people';

    protected $fillable = [
        'first_name',
        'second_name',
        'first_surname',
        'second_surname',
        'identification_number',
        'phone',
        'cellphone',
        'birthdate',
        'place_birth',

        'main_street',
        'secondary_street',
        'neighborhood',
        'reference',

        'marital_status_id',
        'type_identification_id',
        'sex_id',
        'nationality_id',
        'education_level_id',
        'countries_id',
        'provinces_id',
        'cities_id',
    ];

    protected $casts = [
        'birthdate' => 'date',
    ];

    public function getFullNameAttribute()
    {
        return trim("{$this->first_name} {$this->second_name} {$this->first_surname} {$this->second_surname}");
    }
    
    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function maritalStatus()
    {
        return $this->belongsTo(\App\Models\MaritalStatus::class);
    }

    public function typeIdentification()
    {
        return $this->belongsTo(\App\Models\TypeIdentification::class);
    }

    public function sex()
    {
        return $this->belongsTo(\App\Models\Sex::class);
    }

    public function nationality()
    {
        return $this->belongsTo(\App\Models\Nationality::class);
    }

    public function educationLevel()
    {
        return $this->belongsTo(\App\Models\EducationLevel::class);
    }

    public function country()
    {
        return $this->belongsTo(\App\Models\Country::class, 'countries_id');
    }

    public function province()
    {
        return $this->belongsTo(\App\Models\Province::class, 'provinces_id');
    }

    public function city()
    {
        return $this->belongsTo(\App\Models\City::class, 'cities_id');
    }
}