<?php

namespace Modules\Academic\Services;

use Modules\Academic\Models\Attendance;
use Modules\Academic\Models\Section;
use Modules\Academic\Models\Schedule;
use Modules\People\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceService
{
    const ABSENCE_LIMIT_PERCENT = 25;

    /**
     * Obtiene la lista de estudiantes de una sección con su asistencia
     * para una fecha dada.
     */
    public function getAttendanceSheet(Section $section, string $date): array
    {
        $students = $section->enrollmentItems()
            ->where('active', true)
            ->with('enrollment.student.person')
            ->get()
            ->map(fn ($item) => $item->enrollment->student)
            ->unique('id')
            ->sortBy('person.first_surname');

        $existing = Attendance::where('section_id', $section->id)
            ->where('date', $date)
            ->get()
            ->keyBy('student_id');

        $totalClasses = $this->getTotalClassesDone($section, $date);

        return $students->map(function ($student) use ($existing, $section, $totalClasses) {
            $record  = $existing->get($student->id);
            $summary = $this->getStudentSummary($section->id, $student->id);

            return [
                'student_id'         => $student->id,
                'full_name'          => $student->person->full_name,
                'enrollment_number'  => $student->enrollment_number,
                'status'             => $record?->status ?? null,
                'justified'          => $record?->justified ?? false,
                'justification_note' => $record?->justification_note ?? null,
                'attendance_id'      => $record?->id ?? null,
                'summary'            => $summary,
                'at_risk'            => $this->isAtRisk($summary, $totalClasses),
                'auto_fail'          => $this->shouldAutoFail($summary, $totalClasses),
            ];
        })->values()->all();
    }

    /**
     * Guarda o actualiza la asistencia de múltiples estudiantes en una fecha.
     */
    public function saveSheet(Section $section, string $date, array $records, ?int $scheduleId = null): void
    {
        $userId = Auth::id();

        foreach ($records as $record) {
            Attendance::updateOrCreate(
                [
                    'section_id' => $section->id,
                    'student_id' => $record['student_id'],
                    'date'       => $date,
                ],
                [
                    'schedule_id'        => $scheduleId,
                    'recorded_by'        => $userId,
                    'status'             => $record['status'],
                    'justified'          => $record['justified'] ?? false,
                    'justification_note' => $record['justification_note'] ?? null,
                ]
            );
        }
    }

    /**
     * Justifica o quita justificación de una asistencia.
     */
    public function toggleJustification(Attendance $attendance, bool $justified, ?string $note = null): void
    {
        $attendance->update([
            'justified'          => $justified,
            'justification_note' => $note,
        ]);
    }

    /**
     * Resumen de asistencia de un estudiante en una sección.
     */
    public function getStudentSummary(int $sectionId, int $studentId): array
    {
        $records = Attendance::where('section_id', $sectionId)
            ->where('student_id', $studentId)
            ->get();

        $present   = $records->where('status', 'present')->count();
        $late      = $records->where('status', 'late')->count();
        $absent    = $records->where('status', 'absent')->count();
        $justified = $records->where('status', 'absent')->where('justified', true)->count();

        // Las tardanzas cuentan como media falta
        $effectiveAbsences = $absent + ($late * 0.5);

        return [
            'present'            => $present,
            'late'               => $late,
            'absent'             => $absent,
            'justified'          => $justified,
            'effective_absences' => $effectiveAbsences,
            'total_recorded'     => $records->count(),
        ];
    }

    /**
     * Resumen completo de asistencia de toda la sección.
     */
    public function getSectionSummary(Section $section): array
    {
        $totalClasses = $this->getTotalClassesDone($section, now()->toDateString());

        $students = $section->enrollmentItems()
            ->where('active', true)
            ->with('enrollment.student.person')
            ->get()
            ->map(fn ($item) => $item->enrollment->student)
            ->unique('id');

        return $students->map(function ($student) use ($section, $totalClasses) {
            $summary = $this->getStudentSummary($section->id, $student->id);

            return [
                'student_id'        => $student->id,
                'full_name'         => $student->person->full_name,
                'enrollment_number' => $student->enrollment_number,
                'summary'           => $summary,
                'attendance_pct'    => $totalClasses > 0
                    ? round((($summary['present'] + $summary['late'] * 0.5) / $totalClasses) * 100, 1)
                    : null,
                'absence_pct'       => $totalClasses > 0
                    ? round(($summary['effective_absences'] / $totalClasses) * 100, 1)
                    : null,
                'at_risk'           => $this->isAtRisk($summary, $totalClasses),
                'auto_fail'         => $this->shouldAutoFail($summary, $totalClasses),
            ];
        })->sortBy('full_name')->values()->all();
    }

    /**
     * Fechas en las que ya se registró asistencia para una sección.
     */
    public function getRecordedDates(int $sectionId): array
    {
        return Attendance::where('section_id', $sectionId)
            ->select('date')
            ->distinct()
            ->orderBy('date')
            ->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->all();
    }

    // ── Helpers privados ──────────────────────────────────────────────────────

    private function getTotalClassesDone(Section $section, string $upToDate): int
    {
        return Attendance::where('section_id', $section->id)
            ->where('date', '<=', $upToDate)
            ->select('date')
            ->distinct()
            ->count();
    }

    private function isAtRisk(array $summary, int $totalClasses): bool
    {
        if ($totalClasses === 0) return false;
        $pct = ($summary['effective_absences'] / $totalClasses) * 100;
        return $pct >= (self::ABSENCE_LIMIT_PERCENT * 0.75) && $pct < self::ABSENCE_LIMIT_PERCENT;
    }

    private function shouldAutoFail(array $summary, int $totalClasses): bool
    {
        if ($totalClasses === 0) return false;
        $pct = ($summary['effective_absences'] / $totalClasses) * 100;
        return $pct >= self::ABSENCE_LIMIT_PERCENT;
    }
}