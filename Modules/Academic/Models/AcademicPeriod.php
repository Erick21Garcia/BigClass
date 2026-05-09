<?php

namespace Modules\Academic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AcademicPeriod extends Model
{
    use HasFactory;

    protected $table = 'academic_periods';

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'closed_at',
        'closed_by',
        'status',
        'active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'closed_at'  => 'datetime',
        'active'  => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function getIsOpenAttribute()
    {
        return $this->status === 'active';
    }

}