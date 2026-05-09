<?php

namespace Modules\Academic\Services;

use Modules\Academic\Models\AcademicPeriod;

class AcademicPeriodService
{
    public function create(array $data): AcademicPeriod
    {
        if ($data['active'] ?? false) {
            $this->deactivateAll();
        }

        return AcademicPeriod::create($data);
    }

    public function update(AcademicPeriod $period, array $data): AcademicPeriod
    {
        $becomingActive = ($data['active'] ?? false) && !$period->active;

        if ($becomingActive) {
            $this->deactivateAll(except: $period->id);
        }

        $period->update($data);

        return $period;
    }

    public function delete(AcademicPeriod $period): void
    {
        if ($period->active) {
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
            ->where('active', true)
            ->when($except, fn ($q) => $q->where('id', '!=', $except))
            ->update(['active' => false]);
    }
}