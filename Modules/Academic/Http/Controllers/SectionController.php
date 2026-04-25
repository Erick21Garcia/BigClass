<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Academic\Models\Section;
use Modules\Academic\Models\AcademicPeriod;
use Modules\Institucion\Models\Curriculum;

class SectionController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'curricula_id'       => ['required', 'integer', 'exists:curricula,id'],
            'teacher_id'         => ['required', 'integer', 'exists:users,id'],
            'academic_period_id' => ['required', 'integer', 'exists:academic_periods,id'],
            'name'               => ['required', 'string', 'max:100'],
            'quota'              => ['required', 'integer', 'min:1', 'max:500'],
        ], [
            'curricula_id.required'       => 'La materia es obligatoria.',
            'teacher_id.required'         => 'El docente es obligatorio.',
            'academic_period_id.required' => 'El período académico es obligatorio.',
            'name.required'               => 'El nombre de la sección es obligatorio.',
            'quota.required'              => 'El cupo es obligatorio.',
            'quota.min'                   => 'El cupo mínimo es 1.',
        ]);

        $section = Section::create($validated);

        return response()->json([
            'success' => true,
            'section' => $section->load('curriculum.subject', 'teacher', 'academicPeriod'),
        ]);
    }

    public function update(Request $request, Section $section)
    {
        $validated = $request->validate([
            'curricula_id' => ['required', 'integer', 'exists:curricula,id'],
            'name'         => ['required', 'string', 'max:100'],
            'quota'        => ['required', 'integer', 'min:1', 'max:500'],
            'teacher_id'   => ['required', 'integer', 'exists:teachers,id'],
            'active'       => ['boolean'],
        ]);

        $section->update($validated);

        return response()->json(['success' => true]);
    }

    public function destroy(Section $section)
    {
        if ($section->schedules()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar una sección con horarios asignados.',
            ], 422);
        }

        $section->delete();

        return response()->json(['success' => true]);
    }
}