<?php

namespace Modules\Academic\Services;

use Illuminate\Support\Collection;
use Modules\Academic\Models\AcademicPeriod;
use Modules\Academic\Models\Enrollment;
use Modules\Academic\Models\EvaluationParameter;
use Modules\Academic\Models\Schedule;
use Modules\Academic\Models\Section;
use Modules\Institucion\Models\Career;
use Modules\People\Models\Student;
use Modules\People\Models\Teacher;

class ExportService
{
    public function studentsBySection(int $sectionId): Collection
    {
        $section = Section::with([
            'curriculum.subject',
            'teacher.person',
            'academicPeriod',
            'enrollmentItems' => fn ($q) => $q->where('active', true)
                ->with('enrollment.student.person'),
        ])->findOrFail($sectionId);

        return $section->enrollmentItems->map(fn ($item) => [
            'Nro. Matrícula'  => $item->enrollment->student->enrollment_number,
            'Apellidos'       => $item->enrollment->student->person->first_surname
                . ' ' . $item->enrollment->student->person->second_surname,
            'Nombres'         => $item->enrollment->student->person->first_name
                . ' ' . $item->enrollment->student->person->second_name,
            'Cédula'          => $item->enrollment->student->person->identification,
            'Email'           => $item->enrollment->student->person->email ?? '-',
            'Teléfono'        => $item->enrollment->student->person->phone ?? '-',
            'Estado'          => $item->status,
        ])->sortBy('Apellidos')->values();
    }

    public function studentsBySectionMeta(int $sectionId): array
    {
        $section = Section::with('curriculum.subject', 'academicPeriod', 'teacher.person')
            ->findOrFail($sectionId);

        return [
            'filename' => "nomina-{$section->curriculum->subject->name}-{$section->name}.xlsx",
            'sheet'    => 'Nómina',
            'title'    => "Nómina — {$section->curriculum->subject->name} ({$section->name})",
        ];
    }

    public function gradeReport(int $sectionId, int $academicPeriodId): Collection
    {
        $section = Section::with([
            'curriculum.subject',
            'enrollmentItems' => fn ($q) => $q->where('active', true)
                ->with([
                    'enrollment.student.person',
                    'grades.evaluationParameter',
                ]),
        ])->findOrFail($sectionId);

        $parameters = EvaluationParameter::where('academic_period_id', $academicPeriodId)
            ->whereNull('curriculum_id')
            ->where('active', true)
            ->orderBy('is_final')
            ->orderBy('name')
            ->get();

        return $section->enrollmentItems->map(function ($item) use ($parameters) {
            $gradesByParam = $item->grades->keyBy('evaluation_parameter_id');

            $row = [
                'Nro. Matrícula' => $item->enrollment->student->enrollment_number,
                'Apellidos'      => $item->enrollment->student->person->first_surname
                    . ' ' . $item->enrollment->student->person->second_surname,
                'Nombres'        => $item->enrollment->student->person->first_name
                    . ' ' . $item->enrollment->student->person->second_name,
            ];

            foreach ($parameters as $param) {
                $row[$param->name] = isset($gradesByParam[$param->id])
                    ? (float) $gradesByParam[$param->id]->score
                    : null;
            }

            $row['Nota Final'] = $item->final_grade;
            $row['Estado']     = $item->status;

            return $row;
        })->sortBy('Apellidos')->values();
    }

    public function gradeReportMeta(int $sectionId, int $academicPeriodId): array
    {
        $section = Section::with('curriculum.subject')->findOrFail($sectionId);
        $period  = AcademicPeriod::findOrFail($academicPeriodId);

        return [
            'filename' => "calificaciones-{$section->curriculum->subject->name}-{$period->name}.xlsx",
            'sheet'    => 'Calificaciones',
            'title'    => "Calificaciones — {$section->curriculum->subject->name} | {$period->name}",
        ];
    }

    public function kardexByCareer(int $careerId): Collection
    {
        $enrollments = Enrollment::where('career_id', $careerId)
            ->whereIn('status', ['completed', 'active'])
            ->with([
                'student.person',
                'semester',
                'academicPeriod',
                'items' => fn ($q) => $q->where('active', true)
                    ->with('curriculum.subject'),
            ])
            ->orderBy('academic_period_id')
            ->get();

        $rows = collect();

        foreach ($enrollments as $enrollment) {
            foreach ($enrollment->items->where('active', true) as $item) {
                $rows->push([
                    'Nro. Matrícula' => $enrollment->student->enrollment_number,
                    'Apellidos'      => $enrollment->student->person->first_surname
                        . ' ' . $enrollment->student->person->second_surname,
                    'Nombres'        => $enrollment->student->person->first_name
                        . ' ' . $enrollment->student->person->second_name,
                    'Período'        => $enrollment->academicPeriod->name,
                    'Semestre'       => $enrollment->semester->name,
                    'Código'         => $item->curriculum->subject->code,
                    'Materia'        => $item->curriculum->subject->name,
                    'Créditos'       => $item->curriculum->subject->credits,
                    'Nota Final'     => $item->final_grade,
                    'Estado'         => $item->status,
                ]);
            }
        }

        return $rows;
    }

    public function kardexByCareerMeta(int $careerId): array
    {
        $career = Career::findOrFail($careerId);

        return [
            'filename' => "kardex-{$career->name}.xlsx",
            'sheet'    => 'Kardex',
            'title'    => "Kardex — {$career->name}",
        ];
    }

    public function enrollmentsByPeriod(int $academicPeriodId): Collection
    {
        $enrollments = Enrollment::where('academic_period_id', $academicPeriodId)
            ->with([
                'student.person',
                'career',
                'semester',
                'items' => fn ($q) => $q->where('active', true),
            ])
            ->get();

        return $enrollments->map(fn ($enrollment) => [
            'Nro. Matrícula'  => $enrollment->student->enrollment_number,
            'Apellidos'       => $enrollment->student->person->first_surname
                . ' ' . $enrollment->student->person->second_surname,
            'Nombres'         => $enrollment->student->person->first_name
                . ' ' . $enrollment->student->person->second_name,
            'Cédula'          => $enrollment->student->person->identification,
            'Carrera'         => $enrollment->career->name,
            'Semestre'        => $enrollment->semester->name,
            'Materias'        => $enrollment->items->count(),
            'Estado Matrícula' => $enrollment->status,
        ])->sortBy('Apellidos')->values();
    }

    public function enrollmentsByPeriodMeta(int $academicPeriodId): array
    {
        $period = AcademicPeriod::findOrFail($academicPeriodId);

        return [
            'filename' => "matriculas-{$period->name}.xlsx",
            'sheet'    => 'Matrículas',
            'title'    => "Matrículas — {$period->name}",
        ];
    }

    public function scheduleByTeacher(int $teacherId, int $academicPeriodId): Collection
    {
        $days = [0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
                 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado'];

        $schedules = Schedule::whereHas('section', fn ($q) =>
                $q->where('teacher_id', $teacherId)
                  ->where('academic_period_id', $academicPeriodId)
                  ->where('active', true)
            )
            ->where('active', true)
            ->with('section.curriculum.subject', 'classroom')
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return $schedules->map(fn ($schedule) => [
            'Día'      => $days[$schedule->day_of_week] ?? $schedule->day_of_week,
            'Inicio'   => $schedule->start_time,
            'Fin'      => $schedule->end_time,
            'Materia'  => $schedule->section->curriculum->subject->name,
            'Sección'  => $schedule->section->name,
            'Aula'     => $schedule->classroom->name,
            'Tipo'     => $schedule->classroom->type,
            'Recurrente' => $schedule->is_recurring ? 'Sí' : 'No',
        ]);
    }

    public function scheduleByTeacherMeta(int $teacherId, int $academicPeriodId): array
    {
        $teacher = Teacher::with('person')->findOrFail($teacherId);
        $period  = AcademicPeriod::findOrFail($academicPeriodId);

        return [
            'filename' => "horario-{$teacher->person->first_surname}-{$period->name}.xlsx",
            'sheet'    => 'Horario',
            'title'    => "Horario — {$teacher->person->full_name} | {$period->name}",
        ];
    }

    public function attendanceReport(int $sectionId): Collection
    {
        $section = Section::with([
            'curriculum.subject',
            'enrollmentItems' => fn ($q) => $q->where('active', true)
                ->with([
                    'enrollment.student.person',
                    'attendances',
                ]),
        ])->findOrFail($sectionId);

        return $section->enrollmentItems->map(function ($item) {
            $attendances  = $item->attendances ?? collect();
            $total        = $attendances->count();
            $present      = $attendances->where('status', 'present')->count();
            $absent       = $attendances->where('status', 'absent')->count();
            $justified    = $attendances->where('status', 'justified')->count();
            $percentage   = $total > 0 ? round(($present / $total) * 100, 1) : null;

            return [
                'Nro. Matrícula'   => $item->enrollment->student->enrollment_number,
                'Apellidos'        => $item->enrollment->student->person->first_surname
                    . ' ' . $item->enrollment->student->person->second_surname,
                'Nombres'          => $item->enrollment->student->person->first_name
                    . ' ' . $item->enrollment->student->person->second_name,
                'Total Clases'     => $total,
                'Asistencias'      => $present,
                'Faltas'           => $absent,
                'Justificadas'     => $justified,
                '% Asistencia'     => $percentage,
            ];
        })->sortBy('Apellidos')->values();
    }

    public function attendanceReportMeta(int $sectionId): array
    {
        $section = Section::with('curriculum.subject', 'academicPeriod')->findOrFail($sectionId);

        return [
            'filename' => "asistencia-{$section->curriculum->subject->name}-{$section->name}.xlsx",
            'sheet'    => 'Asistencia',
            'title'    => "Asistencia — {$section->curriculum->subject->name} ({$section->name})",
        ];
    }

    public function approvalStats(int $academicPeriodId): Collection
    {
        $sections = Section::where('academic_period_id', $academicPeriodId)
            ->where('active', true)
            ->with([
                'curriculum.subject',
                'teacher.person',
                'enrollmentItems' => fn ($q) => $q->where('active', true),
            ])
            ->get();

        return $sections->map(function ($section) {
            $items     = $section->enrollmentItems;
            $total     = $items->count();
            $approved  = $items->where('status', 'aprobado')->count();
            $failed    = $items->where('status', 'reprobado')->count();
            $pending   = $items->whereNotIn('status', ['aprobado', 'reprobado'])->count();
            $average   = $items->whereNotNull('final_grade')->avg('final_grade');

            return [
                'Materia'        => $section->curriculum->subject->name,
                'Código'         => $section->curriculum->subject->code,
                'Sección'        => $section->name,
                'Docente'        => $section->teacher->person->full_name,
                'Total Alumnos'  => $total,
                'Aprobados'      => $approved,
                'Reprobados'     => $failed,
                'Pendientes'     => $pending,
                '% Aprobación'   => $total > 0 ? round(($approved / $total) * 100, 1) : null,
                'Promedio'       => $average ? round($average, 2) : null,
            ];
        })->sortBy('Materia')->values();
    }

    public function approvalStatsMeta(int $academicPeriodId): array
    {
        $period = AcademicPeriod::findOrFail($academicPeriodId);

        return [
            'filename' => "estadisticas-aprobacion-{$period->name}.xlsx",
            'sheet'    => 'Estadísticas',
            'title'    => "Estadísticas de Aprobación — {$period->name}",
        ];
    }
}