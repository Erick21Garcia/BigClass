<?php

namespace Modules\Academic\Services;

use Modules\Academic\Models\EnrollmentItem;
use Modules\Academic\Models\EvaluationParameter;
use Modules\Academic\Models\Grade;

class GradeService
{
    public function upsert(array $data): Grade
    {
        $item = EnrollmentItem::with('enrollment.academicPeriod')->findOrFail($data['enrollment_item_id']);

        $this->validatePeriodOpen($item);

        if ($item->locked) {
            throw new \DomainException('Las notas están bloqueadas porque el período fue cerrado.');
        }

        $grade = Grade::updateOrCreate(
            [
                'enrollment_item_id'      => $data['enrollment_item_id'],
                'evaluation_parameter_id' => $data['evaluation_parameter_id'],
            ],
            [
                'score'        => $data['score'],
                'observations' => $data['observations'] ?? null,
                'active'       => true,
            ]
        );

        $this->recalculateFinalGrade($grade->enrollmentItem);

        return $grade;
    }

    public function delete(Grade $grade): void
    {
        $item = $grade->enrollmentItem;
        $grade->delete();
        $this->recalculateFinalGrade($item);
    }

    public function recalculateFinalGrade(EnrollmentItem $item): void
    {
        $grades = Grade::where('enrollment_item_id', $item->id)
            ->where('active', true)
            ->with('evaluationParameter')
            ->get();

        if ($grades->isEmpty()) {
            $item->update(['final_grade' => null]);
            return;
        }

        $parameters = EvaluationParameter::forEnrollmentItem($item);

        if ($grades->count() < $parameters->count()) {
            return;
        }

        $weighted = $grades->sum(fn ($grade) =>
            ($grade->score * $grade->evaluationParameter->percentage) / 100
        );

        $finalGrade = round($weighted, 2);
        $status     = $finalGrade >= 7 ? 'aprobado' : 'reprobado';

        $item->update([
            'final_grade' => $finalGrade,
            'status'      => $status,
        ]);
    }

    private function validatePeriodOpen(EnrollmentItem $item): void
    {
        $period = $item->enrollment->academicPeriod;

        if ($period->status === 'closed') {
            throw new \DomainException('No se pueden modificar notas de un período cerrado.');
        }

        if ($period->end_date && now()->gt($period->end_date)) {
            throw new \DomainException("El período '{$period->name}' ya finalizó. No se pueden registrar notas.");
        }
    }
}