<?php

namespace Modules\Institucion\Services;

use Modules\Institucion\Models\Curriculum;

class CurriculumService
{
    public function create(array $data): Curriculum
    {
        return Curriculum::create($data);
    }

    public function update(Curriculum $curriculum, array $data): Curriculum
    {
        $curriculum->update($data);

        return $curriculum;
    }

    public function delete(Curriculum $curriculum): void
    {
        if ($curriculum->enrollmentItems()->exists()) {
            throw new \DomainException('No se puede eliminar el currículo porque tiene matrículas asociadas.');
        }

        $curriculum->delete();
    }
}