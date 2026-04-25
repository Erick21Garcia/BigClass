<?php

namespace Modules\Academic\Services;

use Modules\Academic\Models\AcademicPeriod;

class AcademicPeriodService
{
    public function create(array $data): AcademicPeriod
    {
        if ($data['is_active'] ?? false) {
            $this->deactivateAll();
        }

        return AcademicPeriod::create($data);
    }

    public function update(AcademicPeriod $period, array $data): AcademicPeriod
    {
        $becomingActive = ($data['is_active'] ?? false) && !$period->is_active;

        if ($becomingActive) {
            $this->deactivateAll(except: $period->id);
        }

        $period->update($data);

        return $period;
    }

    public function delete(AcademicPeriod $period): void
    {
        if ($period->is_active) {
            throw new \DomainException('No se puede eliminar el período académico activo.');
        }

        if ($period->status === 'active') {
            throw new \DomainException('No se puede eliminar un período con estado activo.');
        }

        $period->delete();
    }

    private function deactivateAll(int $except = null): void
    {
        AcademicPeriod::query()
            ->where('is_active', true)
            ->when($except, fn ($q) => $q->where('id', '!=', $except))
            ->update(['is_active' => false]);
    }
}