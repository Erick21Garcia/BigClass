<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Academic\Http\Requests\StoreSectionRequest;
use Modules\Academic\Http\Requests\UpdateSectionRequest;
use Modules\Academic\Models\Section;
use Modules\Academic\Services\SectionService;

class SectionController extends Controller
{
    public function __construct(protected SectionService $sectionService)
    {
    }

    public function store(StoreSectionRequest $request)
    {
        $section = $this->sectionService->createSection($request->validated());

        return response()->json([
            'success' => true,
            'section' => $section,
        ]);
    }

    public function update(UpdateSectionRequest $request, Section $section)
    {
        $this->sectionService->updateSection($section, $request->validated());

        return response()->json(['success' => true]);
    }

    public function destroy(Section $section)
    {
        $deleted = $this->sectionService->deleteSection($section);

        if (! $deleted) {
            return response()->json([
                'message' => 'No se puede eliminar una sección con horarios asignados.',
            ], 422);
        }

        return response()->json(['success' => true]);
    }
}