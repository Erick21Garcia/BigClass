<?php

namespace Modules\LMS\Services;

use Modules\LMS\Models\Assignment;
use Modules\LMS\Models\Unit;

class AssignmentService
{
    public function create(Unit $unit, array $data): Assignment
    {
        return Assignment::create([
            'unit_id'                 => $unit->id,
            'evaluation_parameter_id' => $data['evaluation_parameter_id'],
            'title'                   => $data['title'],
            'description'             => $data['description'] ?? null,
            'due_date'                => $data['due_date'],
            'active'                  => true,
        ]);
    }

    public function update(Assignment $assignment, array $data): Assignment
    {
        $assignment->update([
            'title'                   => $data['title'],
            'description'             => $data['description'] ?? null,
            'due_date'                => $data['due_date'],
            'evaluation_parameter_id' => $data['evaluation_parameter_id'],
        ]);

        return $assignment;
    }

    public function deactivate(Assignment $assignment): Assignment
    {
        $assignment->update(['active' => false]);

        return $assignment;
    }
}