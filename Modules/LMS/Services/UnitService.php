<?php

namespace Modules\LMS\Services;

use Modules\LMS\Models\Unit;
use Modules\LMS\Models\VirtualCourse;

class UnitService
{
    public function create(VirtualCourse $course, array $data): Unit
    {
        $nextOrder = $course->units()->max('order') + 1;

        return Unit::create([
            'virtual_course_id' => $course->id,
            'name'              => $data['name'],
            'order'             => $nextOrder,
            'active'            => true,
        ]);
    }

    public function update(Unit $unit, array $data): Unit
    {
        $unit->update([
            'name' => $data['name'],
        ]);

        return $unit;
    }

    /**
     * Reordena las unidades de un curso. $orderedIds es el arreglo de IDs
     * de Unit en el nuevo orden deseado (ej: resultado de un drag-and-drop).
     */
    public function reorder(VirtualCourse $course, array $orderedIds): void
    {
        foreach (array_values($orderedIds) as $index => $unitId) {
            Unit::where('id', $unitId)
                ->where('virtual_course_id', $course->id)
                ->update(['order' => $index + 1]);
        }
    }

    /**
     * No se borra físicamente — se oculta, igual que Subject/Career/Classroom
     * en el ISI, para no perder historial de tareas/notas asociadas.
     */
    public function deactivate(Unit $unit): Unit
    {
        $unit->update(['active' => false]);

        return $unit;
    }
}