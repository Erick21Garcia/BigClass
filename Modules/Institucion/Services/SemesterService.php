<?php

namespace Modules\Institucion\Services;

use Modules\Institucion\Models\Semester;

class SemesterService
{
    public function create(array $data): Semester
    {
        return Semester::create($data);
    }

    public function update(Semester $semester, array $data): Semester
    {
        $semester->update($data);

        return $semester;
    }

    public function delete(Semester $semester): void
    {
        $semester->delete();
    }
}