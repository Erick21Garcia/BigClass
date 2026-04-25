<?php

namespace Modules\Institucion\Services;

use Modules\Institucion\Models\Career;

class CareerService
{
    public function create(array $data): Career
    {
        return Career::create($data);
    }

    public function update(Career $career, array $data): Career
    {
        $career->update($data);

        return $career;
    }

    public function delete(Career $career): void
    {
        if ($career->semesters()->exists()) {
            throw new \DomainException('No se puede eliminar la carrera porque tiene semestres asociados.');
        }

        $career->delete();
    }
}