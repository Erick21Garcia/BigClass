<?php

namespace Modules\Institucion\Services;

use Modules\Institucion\Models\Institution;

class InstitutionService
{
    public function create(array $data): Institution
    {
        return Institution::create($data);
    }

    public function update(Institution $institution, array $data): Institution
    {
        $institution->update($data);

        return $institution;
    }

    public function delete(Institution $institution): void
    {
        if ($institution->faculties()->exists()) {
            throw new \DomainException('No se puede eliminar la institución porque tiene facultades asociadas.');
        }

        $institution->delete();
    }
}