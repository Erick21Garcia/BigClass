<?php

namespace Modules\LMS\Services;

use Modules\Academic\Models\Section;
use Modules\LMS\Models\VirtualCourse;

class VirtualCourseService
{
    /**
     * Crea el Aula Virtual para una Section existente del ISI.
     * Relación 1 a 1 — falla si la Section ya tiene un VirtualCourse.
     */
    public function createForSection(Section $section, array $data = []): VirtualCourse
    {
        if ($section->virtualCourse()->exists()) {
            throw new \RuntimeException(
                "La sección '{$section->name}' ya tiene un Aula Virtual asociada."
            );
        }

        return VirtualCourse::create([
            'section_id'   => $section->id,
            'is_published' => $data['is_published'] ?? false,
            'syllabus'     => $data['syllabus'] ?? null,
        ]);
    }

    public function updateSyllabus(VirtualCourse $course, ?string $syllabus): VirtualCourse
    {
        $course->update(['syllabus' => $syllabus]);

        return $course;
    }

    public function publish(VirtualCourse $course): VirtualCourse
    {
        $course->update(['is_published' => true]);

        return $course;
    }

    public function unpublish(VirtualCourse $course): VirtualCourse
    {
        $course->update(['is_published' => false]);

        return $course;
    }
}