<?php

namespace Modules\Academic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    protected $fillable = [
        'section_id', 
        'classroom_id', 
        'day_of_week',
        'start_time', 
        'end_time', 
        'active',
        'is_recurring', 
        'specific_date', 
        'recurrence_end',
    ];

    protected $casts = [
        'active'         => 'boolean',
        'is_recurring'   => 'boolean',
        'specific_date'  => 'date',
        'recurrence_end' => 'date',
    ];

    const DAYS = [
        0 => 'Domingo',
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(\Modules\Institucion\Models\Classroom::class);
    }

    public function getDayNameAttribute(): string
    {
        return self::DAYS[$this->day_of_week] ?? 'Desconocido';
    }
}