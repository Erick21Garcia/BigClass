<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Academic\Http\Requests\StoreScheduleRequest;
use Modules\Academic\Http\Requests\UpdateScheduleRequest;
use Modules\Academic\Models\Schedule;
use Modules\Academic\Services\ScheduleService;

class ScheduleController extends Controller
{
    public function __construct(protected ScheduleService $scheduleService)
    {
    }

    public function index()
    {
        return Inertia::render('schedules/Index', $this->scheduleService->getIndexData());
    }

    public function events(Request $request)
    {
        $academicPeriodId = $request->integer('academic_period_id');

        abort_if(! $academicPeriodId, 400, 'Se requiere academic_period_id.');

        $events = $this->scheduleService->getEvents($academicPeriodId);

        return response()->json($events);
    }

    public function panel(Request $request)
    {
        $academicPeriodId = $request->integer('academic_period_id');

        abort_if(! $academicPeriodId, 400, 'Se requiere academic_period_id.');

        $data = $this->scheduleService->getPanelData($academicPeriodId);

        return response()->json($data);
    }

    public function store(StoreScheduleRequest $request)
    {
        $conflict = $this->scheduleService->checkConflict($request->validated());

        if ($conflict) {
            return response()->json(['message' => $conflict], 422);
        }

        $schedule = $this->scheduleService->createSchedule($request->validated());

        return response()->json([
            'success'  => true,
            'schedule' => $schedule->load('classroom', 'section.curriculum.subject', 'section.teacher'),
        ]);
    }

    public function update(UpdateScheduleRequest $request, Schedule $schedule)
    {
        $conflict = $this->scheduleService->checkConflict($request->validated(), $schedule->id);

        if ($conflict) {
            return response()->json(['message' => $conflict], 422);
        }

        $this->scheduleService->updateSchedule($schedule, $request->validated());

        return response()->json(['success' => true]);
    }

    public function destroy(Schedule $schedule)
    {
        $schedule->delete();

        return response()->json(['success' => true]);
    }
}