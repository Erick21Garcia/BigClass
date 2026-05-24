<?php

namespace Modules\Academic\Services;

use Modules\Academic\Models\EvaluationParameter;

class EvaluationParameterService
{
    public function create(array $data): EvaluationParameter
    {
        $this->validatePercentage(
            $data['academic_period_id'],
            $data['curriculum_id'] ?? null,
            $data['percentage'],
        );

        return EvaluationParameter::create([
            ...$data,
            'active' => true,
        ]);
    }

    public function update(EvaluationParameter $parameter, array $data): EvaluationParameter
    {
        $this->validatePercentage(
            $parameter->academic_period_id,
            $parameter->curriculum_id,
            $data['percentage'],
            $parameter->id,
        );

        $parameter->update($data);

        return $parameter->fresh();
    }

    public function delete(EvaluationParameter $parameter): void
    {
        if ($parameter->grades()->exists()) {
            throw new \DomainException(
                'No se puede eliminar el parámetro porque ya tiene notas registradas.'
            );
        }

        $parameter->delete();
    }

    private function validatePercentage(
        int $academicPeriodId,
        ?int $curriculumId,
        float $percentage,
        ?int $excludeId = null
    ): void {
        $currentTotal = EvaluationParameter::where('academic_period_id', $academicPeriodId)
            ->where('curriculum_id', $curriculumId)
            ->where('active', true)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->sum('percentage');

        if (($currentTotal + $percentage) > 100) {
            $available = 100 - $currentTotal;
            throw new \DomainException(
                "El porcentaje excede el 100%. Solo puedes asignar hasta {$available}%."
            );
        }
    }
}