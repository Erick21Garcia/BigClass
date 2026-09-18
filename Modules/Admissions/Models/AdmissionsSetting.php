<?php

namespace Modules\Admissions\Models;

use Illuminate\Database\Eloquent\Model;

class AdmissionsSetting extends Model
{
    protected $table = 'admissions_settings';

    protected $fillable = [
        'abandon_after_days',
        'retention_after_months',
    ];

    protected $casts = [
        'abandon_after_days'     => 'integer',
        'retention_after_months' => 'integer',
    ];

    /**
     * Singleton — siempre hay exactamente una fila (sembrada en la
     * migración). Este helper evita repetir ::first() por todos lados
     * y sirve como único punto de acceso a la configuración.
     */
    public static function current(): self
    {
        return static::firstOrCreate([], [
            'abandon_after_days'     => 30,
            'retention_after_months' => 3,
        ]);
    }
}