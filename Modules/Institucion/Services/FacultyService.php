<?php

namespace Modules\Institucion\Services;

use Modules\Institucion\Models\Faculty;

class FacultyService
{
    public function create(array $data): Faculty
    {
        return Faculty::create($data);
    }

    public function update(Faculty $faculty, array $data): Faculty
    {
        $faculty->update($data);

        return $faculty;
    }

    public function delete(Faculty $faculty): void
    {
        if ($faculty->careers()->exists()) {
            throw new \DomainException('No se puede eliminar la facultad porque tiene carreras asociadas.');
        }

        $faculty->delete();
    }
}