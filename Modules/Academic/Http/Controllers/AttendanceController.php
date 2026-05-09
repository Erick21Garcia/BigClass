<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Academic\Models\Attendance;
use Modules\Academic\Models\Section;
use Modules\Academic\Models\AcademicPeriod;
use Modules\Academic\Services\AttendanceService;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceService $service) {}

    /**
     * Vista principal de asistencia de una sección.
     */
    public function index(Request $request, Section $section)
    {
        $section->load([
            'curriculum.subject',
            'curriculum.semester.career',
            'teacher.person',
            'academicPeriod',
            'schedules.classroom',
        ]);

        $date          = $request->input('date', now()->toDateString());
        $sheet         = $this->service->getAttendanceSheet($section, $date);
        $recordedDates = $this->service->getRecordedDates($section->id);
        $summary       = $this->service->getSectionSummary($section);

        // Horario del día seleccionado
        $dayOfWeek      = now()->parse($date)->dayOfWeek;
        $scheduleOfDay  = $section->schedules
            ->where('day_of_week', $dayOfWeek)
            ->where('active', true)
            ->first();

        return Inertia::render('attendance/Index', [
            'section' => [
                'id'      => $section->id,
                'name'    => $section->name,
                'subject' => $section->curriculum->subject->name,
                'career'  => $section->curriculum->semester->career->name,
                'teacher' => $section->teacher->person->full_name,
                'period'  => $section->academicPeriod->name,
                'schedule_today' => $scheduleOfDay ? [
                    'start_time' => substr($scheduleOfDay->start_time, 0, 5),
                    'end_time'   => substr($scheduleOfDay->end_time, 0, 5),
                    'classroom'  => $scheduleOfDay->classroom->name,
                ] : null,
            ],
            'date'          => $date,
            'sheet'         => $sheet,
            'recordedDates' => $recordedDates,
            'summary'       => $summary,
        ]);
    }

    /**
     * Guarda la planilla de asistencia.
     */
    public function store(Request $request, Section $section)
    {
        $request->validate([
            'date'              => ['required', 'date'],
            'records'           => ['required', 'array'],
            'records.*.student_id' => ['required', 'integer'],
            'records.*.status'     => ['required', 'in:present,absent,late'],
            'records.*.justified'  => ['boolean'],
            'records.*.justification_note' => ['nullable', 'string', 'max:300'],
        ]);

        $this->service->saveSheet(
            $section,
            $request->date,
            $request->records,
            $request->schedule_id,
        );

        return response()->json(['success' => true]);
    }

    /**
     * Actualiza la justificación de una asistencia.
     */
    public function justify(Request $request, Attendance $attendance)
    {
        $request->validate([
            'justified'          => ['required', 'boolean'],
            'justification_note' => ['nullable', 'string', 'max:300'],
        ]);

        $this->service->toggleJustification(
            $attendance,
            $request->justified,
            $request->justification_note,
        );

        return response()->json(['success' => true]);
    }

    /**
     * Devuelve la planilla de un día específico (para recargar sin reload).
     */
    public function sheet(Request $request, Section $section)
    {
        $date  = $request->input('date', now()->toDateString());
        $sheet = $this->service->getAttendanceSheet($section, $date);

        return response()->json($sheet);
    }
}