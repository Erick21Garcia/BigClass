<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Academic\Models\AcademicPeriod;
use Modules\Academic\Models\Enrollment;
use Modules\Academic\Models\EnrollmentItem;
use Modules\Academic\Models\Grade;
use Modules\Academic\Models\Section;
use Modules\Academic\Models\Schedule;
use Modules\People\Models\Student;
use Modules\People\Models\Teacher;

class DashboardController extends Controller
{
    public function admin()
    {
        $activePeriod = AcademicPeriod::active()->latest()->first();

        // Stats principales
        $totalStudents  = Student::where('active', true)->count();
        $totalTeachers  = Teacher::where('active', true)->count();
        $activeSections = $activePeriod
            ? Section::where('academic_period_id', $activePeriod->id)->where('active', true)->count()
            : 0;

        // Tasa de aprobación del período activo
        $approvalRate = null;
        if ($activePeriod) {
            $total    = EnrollmentItem::whereHas('enrollment', fn ($q) =>
                $q->where('academic_period_id', $activePeriod->id)
            )->whereIn('status', ['aprobado', 'reprobado'])->count();

            $approved = EnrollmentItem::whereHas('enrollment', fn ($q) =>
                $q->where('academic_period_id', $activePeriod->id)
            )->where('status', 'aprobado')->count();

            $approvalRate = $total > 0 ? round(($approved / $total) * 100, 1) : null;
        }

        // Promedio general
        $generalAvg = EnrollmentItem::whereHas('enrollment', fn ($q) =>
            $q->where('academic_period_id', $activePeriod?->id)
        )->whereNotNull('final_grade')->avg('final_grade');

        // Evaluaciones pendientes (items en curso sin todas las notas)
        $pendingGrades = EnrollmentItem::whereHas('enrollment', fn ($q) =>
            $q->where('academic_period_id', $activePeriod?->id)
              ->whereIn('status', ['active', 'registered'])
        )->where('status', 'en_curso')->whereNull('final_grade')->count();

        // Top materias por inscripción
        $topSubjects = EnrollmentItem::whereHas('enrollment', fn ($q) =>
                $q->where('academic_period_id', $activePeriod?->id)
            )
            ->with('curriculum.subject')
            ->get()
            ->groupBy('curricula_id')
            ->map(fn ($items) => [
                'name'       => $items->first()->curriculum->subject->name,
                'students'   => $items->count(),
                'completion' => $items->whereNotNull('final_grade')->count() > 0
                    ? round(($items->where('status', 'aprobado')->count() / $items->count()) * 100)
                    : 0,
                'avg'        => round($items->whereNotNull('final_grade')->avg('final_grade') ?? 0, 1),
            ])
            ->sortByDesc('students')
            ->take(5)
            ->values();

        // Conflictos de horario
        $conflicts = \Modules\Academic\Models\Schedule::whereHas('section', fn ($q) =>
                $q->where('academic_period_id', $activePeriod?->id)->where('active', true)
            )
            ->where('active', true)
            ->get()
            ->groupBy(fn ($s) => "{$s->classroom_id}-{$s->day_of_week}-{$s->start_time}")
            ->filter(fn ($group) => $group->count() > 1)
            ->count();

        return Inertia::render('dashboards/DashboardAdmin', [
            'stats' => [
                'total_students'   => $totalStudents,
                'total_teachers'   => $totalTeachers,
                'active_sections'  => $activeSections,
                'approval_rate'    => $approvalRate,
                'general_avg'      => $generalAvg ? round($generalAvg, 1) : null,
                'pending_grades'   => $pendingGrades,
                'conflicts'        => $conflicts,
            ],
            'topSubjects'  => $topSubjects,
            'activePeriod' => $activePeriod?->name ?? 'Sin período activo',
        ]);
    }

    public function docente(Request $request)
    {
        $user    = $request->user();
        $teacher = \Modules\People\Models\Teacher::whereHas('person', fn ($q) =>
            $q->whereHas('user', fn ($q2) => $q2->where('id', $user->id))
        )->first();

        $activePeriod = AcademicPeriod::active()->latest()->first();

        if (! $teacher || ! $activePeriod) {
            return Inertia::render('dashboards/DashboardDocente', [
                'stats'           => [],
                'myCourses'       => [],
                'upcomingClasses' => [],
                'activePeriod'    => $activePeriod?->name ?? 'Sin período activo',
            ]);
        }

        // Secciones del docente en el período activo
        $sections = Section::where('teacher_id', $teacher->id)
            ->where('academic_period_id', $activePeriod->id)
            ->where('active', true)
            ->with([
                'curriculum.subject',
                'schedules.classroom',
                'enrollmentItems.grades',
            ])
            ->get();

        $totalStudents  = $sections->sum(fn ($s) => $s->enrollmentItems->count());
        $pendingGrades  = $sections->sum(fn ($s) =>
            $s->enrollmentItems->where('status', 'en_curso')->whereNull('final_grade')->count()
        );

        // Mis cursos con stats
        $myCourses = $sections->map(fn ($section) => [
            'name'     => $section->curriculum->subject->name,
            'students' => $section->enrollmentItems->count(),
            'pending'  => $section->enrollmentItems->where('status', 'en_curso')->whereNull('final_grade')->count(),
            'avg'      => round($section->enrollmentItems->whereNotNull('final_grade')->avg('final_grade') ?? 0, 1),
        ])->values();

        // Próximas clases (horarios de la semana)
        $dayNames = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
        $today    = now()->dayOfWeek;

        $upcomingClasses = $sections->flatMap(fn ($section) =>
            $section->schedules->where('active', true)->map(fn ($schedule) => [
                'subject'  => $section->curriculum->subject->name,
                'time'     => substr($schedule->start_time, 0, 5) . ' - ' . substr($schedule->end_time, 0, 5),
                'room'     => $schedule->classroom->name,
                'day'      => $dayNames[$schedule->day_of_week],
                'day_num'  => $schedule->day_of_week,
                'is_today' => $schedule->day_of_week === $today,
            ])
        )
        ->sortBy('day_num')
        ->take(6)
        ->values();

        return Inertia::render('dashboards/DashboardDocente', [
            'stats' => [
                'my_courses'     => $sections->count(),
                'total_students' => $totalStudents,
                'pending_grades' => $pendingGrades,
            ],
            'myCourses'       => $myCourses,
            'upcomingClasses' => $upcomingClasses,
            'activePeriod'    => $activePeriod->name,
        ]);
    }

    public function estudiante(Request $request)
    {
        $user    = $request->user();
        $student = \Modules\People\Models\Student::whereHas('person', fn ($q) =>
            $q->whereHas('user', fn ($q2) => $q2->where('id', $user->id))
        )->first();

        $activePeriod = AcademicPeriod::active()->latest()->first();

        if (! $student || ! $activePeriod) {
            return Inertia::render('dashboards/DashboardEstudiante', [
                'stats'        => [],
                'subjects'     => [],
                'schedule'     => [],
                'activePeriod' => $activePeriod?->name ?? 'Sin período activo',
            ]);
        }

        $enrollment = Enrollment::where('student_id', $student->id)
            ->where('academic_period_id', $activePeriod->id)
            ->whereIn('status', ['active', 'registered'])
            ->with([
                'items.curriculum.subject',
                'items.grades.evaluationParameter',
                'items.section.schedules.classroom',
                'semester',
            ])
            ->first();

        $subjects = collect();
        $schedule = collect();

        if ($enrollment) {
            $subjects = $enrollment->items->where('active', true)->map(fn ($item) => [
                'name'        => $item->curriculum->subject->name,
                'code'        => $item->curriculum->subject->code,
                'credits'     => $item->curriculum->subject->credits,
                'final_grade' => $item->final_grade,
                'status'      => $item->status,
                'grades'      => $item->grades->map(fn ($g) => [
                    'name'  => $g->evaluationParameter->name,
                    'score' => $g->score,
                    'pct'   => $g->evaluationParameter->percentage,
                ])->values(),
            ])->values();

            $dayNames = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
            $schedule = $enrollment->items->where('active', true)
                ->flatMap(fn ($item) =>
                    $item->section?->schedules->where('active', true)->map(fn ($sch) => [
                        'subject'  => $item->curriculum->subject->name,
                        'day'      => $dayNames[$sch->day_of_week],
                        'day_num'  => $sch->day_of_week,
                        'time'     => substr($sch->start_time, 0, 5) . ' - ' . substr($sch->end_time, 0, 5),
                        'room'     => $sch->classroom->name,
                        'is_today' => $sch->day_of_week === now()->dayOfWeek,
                    ]) ?? collect()
                )
                ->sortBy('day_num')
                ->values();
        }

        $approved  = $subjects->where('status', 'aprobado')->count();
        $total     = $subjects->count();
        $avg       = $subjects->whereNotNull('final_grade')->avg('final_grade');

        return Inertia::render('dashboards/DashboardEstudiante', [
            'stats' => [
                'enrolled_subjects' => $total,
                'approved'          => $approved,
                'avg'               => $avg ? round($avg, 2) : null,
                'semester'          => $enrollment?->semester->name ?? '—',
            ],
            'subjects'     => $subjects,
            'schedule'     => $schedule,
            'activePeriod' => $activePeriod->name,
        ]);
    }
}