<?php

namespace Modules\LMS\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Modules\LMS\Http\Requests\ReorderUnitsRequest;
use Modules\LMS\Http\Requests\StoreUnitRequest;
use Modules\LMS\Models\Unit;
use Modules\LMS\Models\VirtualCourse;
use Modules\LMS\Services\UnitService;

class UnitController extends Controller
{
    public function __construct(
        private readonly UnitService $unitService
    ) {}

    public function store(StoreUnitRequest $request, VirtualCourse $virtualCourse): RedirectResponse
    {
        $this->unitService->create($virtualCourse, $request->validated());

        return back()->with('success', 'Unidad creada correctamente.');
    }

    public function update(StoreUnitRequest $request, Unit $unit): RedirectResponse
    {
        $this->unitService->update($unit, $request->validated());

        return back()->with('success', 'Unidad actualizada.');
    }

    public function reorder(ReorderUnitsRequest $request, VirtualCourse $virtualCourse): RedirectResponse
    {
        $this->unitService->reorder($virtualCourse, $request->validated('unit_ids'));

        return back()->with('success', 'Orden actualizado.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $this->unitService->deactivate($unit);

        return back()->with('success', 'Unidad eliminada.');
    }
}