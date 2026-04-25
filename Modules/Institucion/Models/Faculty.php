<?php

namespace Modules\Institucion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Faculty extends Model
{
    use HasFactory;

    protected $table = 'faculties';

    protected $fillable = [
        'institution_id',
        'name',
        'code',
        'description',
        'dean_name',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function careers()
    {
        return $this->hasMany(Career::class);
    }
}