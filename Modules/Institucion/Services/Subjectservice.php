<?php

namespace Modules\Institucion\Services;

use Modules\Institucion\Models\Subject;

class SubjectService
{
    public function create(array $data): Subject
    {
        return Subject::create($data);
    }

    public function update(Subject $subject, array $data): Subject
    {
        $subject->update($data);

        return $subject;
    }

    public function delete(Subject $subject): void
    {
        if ($subject->prerequisiteSubjects()->exists()) {
            throw new \DomainException('No se puede eliminar la materia porque otras materias la tienen como prerequisito.');
        }

        if ($subject->isPrerequisiteOf()->exists()) {
            throw new \DomainException('No se puede eliminar la materia porque es prerequisito de otras materias.');
        }

        $subject->delete();
    }
}