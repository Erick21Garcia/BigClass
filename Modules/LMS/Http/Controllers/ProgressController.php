<?php

namespace Modules\LMS\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\LMS\Http\Requests\MarkResourceViewedRequest;
use Modules\LMS\Models\Resource;
use Modules\LMS\Models\VirtualCourse;
use Modules\LMS\Services\ProgressService;
use Modules\People\Models\Student;

class ProgressController extends Controller
{
    public function __construct(
        private readonly ProgressService $progressService
    ) {}

    public function markViewed(MarkResourceViewedRequest $request, Resource $resource): RedirectResponse
    {
        $student = Student::whereHas('person', fn ($q) => $q->where('user_id', $request->user()->id))
            ->firstOrFail();

        $this->progressService->markResourceAsViewed($resource, $student);

        return back()->with('success', 'Marcado como leído.');
    }

    /**
     * Progreso del curso completo para el estudiante autenticado, desglosado
     * por Unidad. Pensado para consumirse desde el frontend (vista "Mis
     * cursos" / detalle del curso).
     */
    public function show(Request $request, VirtualCourse $virtualCourse): JsonResponse
    {
        $student = Student::whereHas('person', fn ($q) => $q->where('user_id', $request->user()->id))
            ->firstOrFail();

        $units = $virtualCourse->units()->active()->get();

        return response()->json([
            'course_progress' => $this->progressService->courseProgress($virtualCourse, $student),
            'units' => $units->map(fn ($unit) => [
                'unit_id' => $unit->id,
                'name'    => $unit->name,
                'progress' => $this->progressService->unitProgress($unit, $student),
            ]),
        ]);
    }
}