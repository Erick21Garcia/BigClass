<?php

namespace Modules\Institucion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classroom extends Model
{
    protected $fillable = [
        'name', 
        'code', 
        'capacity', 
        'type', 
        'active'
    ];

    protected $casts = [
        'active' => 'boolean'
    ];

    public function schedules(): HasMany
    {
        return $this->hasMany(\Modules\Academic\Models\Schedule::class);
    }
}