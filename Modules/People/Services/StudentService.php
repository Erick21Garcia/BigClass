<?php

namespace Modules\People\Services;

use Modules\People\Models\Student;

class StudentService
{
    public function create(array $data): Student
    {
        return Student::create($data);
    }

    public function update(Student $student, array $data): Student
    {
        $student->update($data);

        return $student->fresh();
    }

    public function delete(Student $student): void
    {
        $student->delete();
    }
}