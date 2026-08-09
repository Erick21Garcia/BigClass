<?php

namespace Modules\LMS\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Modules\Academic\Models\Section;
use Modules\LMS\Http\Requests\StoreVirtualCourseRequest;
use Modules\LMS\Http\Requests\UpdateVirtualCourseRequest;
use Modules\LMS\Models\VirtualCourse;
use Modules\LMS\Services\VirtualCourseService;

class VirtualCourseController extends Controller
{
    public function __construct(
        private readonly VirtualCourseService $virtualCourseService
    ) {}

    public function store(StoreVirtualCourseRequest $request, Section $section): RedirectResponse
    {
        $this->virtualCourseService->createForSection($section, $request->validated());

        return back()->with('success', 'Aula Virtual creada correctamente.');
    }

    public function update(UpdateVirtualCourseRequest $request, VirtualCourse $virtualCourse): RedirectResponse
    {
        $this->virtualCourseService->updateSyllabus(
            $virtualCourse,
            $request->validated('syllabus')
        );

        return back()->with('success', 'Contenido actualizado.');
    }

    public function publish(VirtualCourse $virtualCourse): RedirectResponse
    {
        $this->virtualCourseService->publish($virtualCourse);

        return back()->with('success', 'Aula Virtual publicada.');
    }

    public function unpublish(VirtualCourse $virtualCourse): RedirectResponse
    {
        $this->virtualCourseService->unpublish($virtualCourse);

        return back()->with('success', 'Aula Virtual despublicada.');
    }
}