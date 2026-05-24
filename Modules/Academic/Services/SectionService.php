<?php

namespace Modules\Academic\Services;

use Modules\Academic\Models\Section;

class SectionService
{
    public function createSection(array $data): Section
    {
        $section = Section::create($data);

        return $section->load('curriculum.subject', 'teacher', 'academicPeriod');
    }

    public function updateSection(Section $section, array $data): Section
    {
        $section->update($data);

        return $section;
    }

    public function deleteSection(Section $section): bool
    {
        if ($section->schedules()->exists()) {
            return false;
        }

        $section->delete();

        return true;
    }
}