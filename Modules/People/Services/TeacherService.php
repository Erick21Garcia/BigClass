<?php

namespace Modules\People\Services;

use Modules\People\Models\Teacher;

class TeacherService
{
    public function create(array $data): Teacher
    {
        return Teacher::create($data);
    }

    public function update(Teacher $teacher, array $data): Teacher
    {
        $teacher->update($data);

        return $teacher;
    }

    public function delete(Teacher $teacher): void
    {
        $teacher->delete();
    }
}