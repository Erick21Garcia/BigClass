<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Academic\Models\Schedule;
use Modules\Academic\Models\Section;
use Modules\Academic\Models\AcademicPeriod;
use Modules\Institucion\Models\Classroom;
use Modules\People\Models\Teacher;
use Modules\Institucion\Models\Curriculum;
use Inertia\Inertia;

class ScheduleController extends Controller
{
    public function index()
    {
        $academicPeriods = AcademicPeriod::where('active', true)
            ->orderByDesc('id')
            ->get(['id', 'name']);

        $teachers = Teacher::where('active', true)
            ->with('person')
            ->get()
            ->map(fn ($t) => [
                'id'        => $t->id,
                'full_name' => $t->person->full_name,
            ]);

        $classrooms = Classroom::where('active', true)
            ->get(['id', 'name', 'code', 'capacity', 'type']);

        $curricula = Curriculum::with('subject', 'semester.career')
            ->get()
            ->map(fn ($c) => [
                'id'      => $c->id,
                'subject' => $c->subject->name,
                'semester'=> $c->semester->name,
                'career'  => $c->semester->career->name,
            ]);

        return Inertia::render('schedules/Index', [
            'academicPeriods' => $academicPeriods,
            'teachers'        => $teachers,
            'classrooms'      => $classrooms,
            'curricula'       => $curricula,
        ]);
    }

    public function events(Request $request)
    {
        $academicPeriodId = $request->integer('academic_period_id');

        abort_if(! $academicPeriodId, 400, 'Se requiere academic_period_id.');

        $sections = Section::where('academic_period_id', $academicPeriodId)
            ->where('active', true)
            ->with([
                'curriculum.subject',
                'teacher.person',
                'schedules.classroom',
            ])
            ->get();

        $events = collect();

        foreach ($sections as $section) {
            foreach ($section->schedules as $schedule) {
                if (! $schedule->active) continue;

                $events->push([
                    'id'             => $schedule->id,
                    'section_id'     => $section->id,
                    'curricula_id' => $section->curricula_id,
                    'teacher_id'     => $section->teacher_id,
                    'section_name'   => $section->name,
                    'classroom_id'   => $schedule->classroom_id,
                    'title'          => $section->curriculum->subject->name,
                    'quota'          => $section->quota,
                    'teacher'        => $section->teacher->person->full_name,
                    'classroom'      => $schedule->classroom->name,
                    'classroom_type' => $schedule->classroom->type,
                    'day_of_week'    => $schedule->day_of_week,
                    'start_time'     => $schedule->start_time,
                    'end_time'       => $schedule->end_time,
                    'is_recurring'   => $schedule->is_recurring,
                    'specific_date'  => $schedule->specific_date,
                    'recurrence_end' => $schedule->recurrence_end,
                    'color'          => $this->subjectColor($section->curricula_id),
                ]);
            }
        }

        return response()->json($events->values());
    }

    public function panel(Request $request)
    {
        $academicPeriodId = $request->integer('academic_period_id');

        abort_if(! $academicPeriodId, 400, 'Se requiere academic_period_id.');

        $unassigned = \Modules\Institucion\Models\Curriculum::whereDoesntHave('sections', fn ($q) =>
                $q->where('academic_period_id', $academicPeriodId)
            )
            ->with('subject')
            ->get()
            ->map(fn ($c) => [
                'curricula_id' => $c->id,
                'subject'      => $c->subject->name,
                'hours'        => $c->hours ?? null,
            ]);

        $conflicts = $this->detectConflicts($academicPeriodId);

        return response()->json([
            'unassigned' => $unassigned,
            'conflicts'  => $conflicts,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'section_id'     => ['required', 'integer', 'exists:sections,id'],
            'classroom_id'   => ['required', 'integer', 'exists:classrooms,id'],
            'day_of_week'    => ['required', 'integer', 'min:0', 'max:6'],
            'start_time'     => ['required', 'date_format:H:i'],
            'end_time'       => ['required', 'date_format:H:i', 'after:start_time'],
            'is_recurring'   => ['boolean'],
            'specific_date'  => ['nullable', 'date', 'required_if:is_recurring,false'],
            'recurrence_end' => ['nullable', 'date'],
        ], [
            'end_time.after'               => 'La hora de fin debe ser posterior a la de inicio.',
            'specific_date.required_if'    => 'La fecha específica es obligatoria para horarios no recurrentes.',
        ]);

        $conflict = $this->checkConflict($validated);
        if ($conflict) {
            return response()->json(['message' => $conflict], 422);
        }

        $schedule = Schedule::create($validated);

        return response()->json([
            'success'  => true,
            'schedule' => $schedule->load('classroom', 'section.curriculum.subject', 'section.teacher'),
        ]);
    }

    public function update(Request $request, Schedule $schedule)
    {
        $validated = $request->validate([
            'classroom_id'   => ['required', 'integer', 'exists:classrooms,id'],
            'day_of_week'    => ['required', 'integer', 'min:0', 'max:6'],
            'start_time'     => ['required', 'date_format:H:i'],
            'end_time'       => ['required', 'date_format:H:i', 'after:start_time'],
            'is_recurring'   => ['boolean'],
            'specific_date'  => ['nullable', 'date'],
            'recurrence_end' => ['nullable', 'date']
        ]);

        $conflict = $this->checkConflict($validated, $schedule->id);
        if ($conflict) {
            return response()->json(['message' => $conflict], 422);
        }

        $schedule->update($validated);

        return response()->json(['success' => true]);
    }

    public function destroy(Schedule $schedule)
    {
        $schedule->delete();
        return response()->json(['success' => true]);
    }

    private function checkConflict(array $data, ?int $excludeId = null): ?string
    {
        $query = Schedule::where('classroom_id', $data['classroom_id'])
            ->where('day_of_week', $data['day_of_week'])
            ->where('active', true)
            ->where('start_time', '<', $data['end_time'])
            ->where('end_time', '>', $data['start_time']);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $clash = $query->with('section.curriculum.subject')->first();

        if ($clash) {
            return "Conflicto de aula: {$clash->section->curriculum->subject->name} ({$clash->start_time} - {$clash->end_time}) ya ocupa esta aula.";
        }

        if (!empty($data['section_id'])) {
            $section  = Section::find($data['section_id']);
            if ($section) {
                $docClash = Schedule::whereHas('section', fn ($q) =>
                        $q->where('teacher_id', $section->teacher_id)
                        ->where('academic_period_id', $section->academic_period_id)
                    )
                    ->where('day_of_week', $data['day_of_week'])
                    ->where('active', true)
                    ->where('start_time', '<', $data['end_time'])
                    ->where('end_time', '>', $data['start_time'])
                    ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                    ->with('section.curriculum.subject')
                    ->first();

                if ($docClash) {
                    return "Conflicto de docente: ya tiene clase de {$docClash->section->curriculum->subject->name} ({$docClash->start_time} - {$docClash->end_time}).";
                }
            }
        }

        return null;
    }

    private function detectConflicts(int $academicPeriodId): array
    {
        $schedules = Schedule::whereHas('section', fn ($q) =>
                $q->where('academic_period_id', $academicPeriodId)->where('active', true)
            )
            ->where('active', true)
            ->with('section.curriculum.subject', 'section.teacher.person', 'classroom')
            ->get();

        $conflicts = [];

        foreach ($schedules as $a) {
            foreach ($schedules as $b) {
                if ($a->id >= $b->id) continue;
                if ($a->day_of_week !== $b->day_of_week) continue;
                if ($a->start_time >= $b->end_time || $a->end_time <= $b->start_time) continue;

                if ($a->classroom_id === $b->classroom_id) {
                    $conflicts[] = [
                        'type'    => 'classroom',
                        'message' => "{$a->classroom->name}: {$a->section->curriculum->subject->name} vs {$b->section->curriculum->subject->name}",
                        'day'     => $a->day_of_week,
                        'time'    => "{$a->start_time} - {$a->end_time}",
                    ];
                }

                if ($a->section->teacher_id === $b->section->teacher_id) {
                    $conflicts[] = [
                        'type'    => 'teacher',
                        'message' => "Prof. {$a->section->teacher->person->full_name}: {$a->section->curriculum->subject->name} vs {$b->section->curriculum->subject->name}",
                        'day'     => $a->day_of_week,
                        'time'    => "{$a->start_time} - {$a->end_time}",
                    ];
                }
            }
        }

        return $conflicts;
    }

    private function subjectColor(int $curriculaId): string
    {
        $colors = ['#4f86c6', '#e07b54', '#6abf69', '#9b6bbf', '#e6b830', '#e05470'];
        return $colors[$curriculaId % count($colors)];
    }
}