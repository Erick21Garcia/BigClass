<?php

namespace Modules\Academic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Modules\Institucion\Models\Curriculum;

class EvaluationParameter extends Model
{
    protected $table = 'evaluation_parameters';

    protected $fillable = [
        'academic_period_id',
        'curriculum_id',
        'name',
        'percentage',
        'is_final',
        'active',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'is_final'   => 'boolean',
        'active'     => 'boolean',
    ];

    public function academicPeriod()
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function curriculum()
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    public static function forEnrollmentItem(EnrollmentItem $item): Collection
    {
        $curriculumId     = $item->curricula_id;
        $academicPeriodId = $item->enrollment->academic_period_id;

        $specific = self::where('academic_period_id', $academicPeriodId)
            ->where('curriculum_id', $curriculumId)
            ->where('active', true)
            ->orderBy('is_final')
            ->orderBy('name')
            ->get();

        if ($specific->isNotEmpty()) {
            return $specific;
        }

        return self::where('academic_period_id', $academicPeriodId)
            ->whereNull('curriculum_id')
            ->where('active', true)
            ->orderBy('is_final')
            ->orderBy('name')
            ->get();
    }

    public static function percentageSumsTo100(int $academicPeriodId, ?int $curriculumId): bool
    {
        $total = self::where('academic_period_id', $academicPeriodId)
            ->where('curriculum_id', $curriculumId)
            ->where('active', true)
            ->sum('percentage');

        return (float) $total === 100.0;
    }
}