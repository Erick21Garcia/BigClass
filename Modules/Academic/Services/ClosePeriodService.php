<?php

namespace Modules\Academic\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Models\AcademicPeriod;
use Modules\Academic\Models\Enrollment;
use Modules\Academic\Models\EnrollmentItem;
use Modules\Academic\Models\Grade;

class ClosePeriodService
{
    private array $report = [
        'period'           => '',
        'locked_grades'    => 0,
        'locked_items'     => 0,
        'approved_items'   => 0,
        'failed_items'     => 0,
        'incomplete_items' => 0,
        'completed_enrollments' => 0,
        'errors'           => [],
    ];

    public function close(AcademicPeriod $period, ?int $userId = null): array
    {
        if ($period->status === 'closed') {
            throw new \RuntimeException("El período '{$period->name}' ya está cerrado.");
        }

        $this->report['period'] = $period->name;
        $userId = $userId ?? Auth::id();

        DB::transaction(function () use ($period, $userId) {

            // ── PASO 1: Bloquear notas ────────────────────────────────────────
            $this->lockGrades($period, $userId);

            // ── PASO 2: Calcular promedios finales ────────────────────────────
            $this->calculateFinalGrades($period);

            // ── PASO 3: Marcar enrollments como completados ───────────────────
            $this->completeEnrollments($period);

            // ── PASO 4: Cerrar el período ─────────────────────────────────────
            $period->update([
                'status'    => 'closed',
                'active' => false,
                'closed_at' => now(),
                'closed_by' => $userId,
            ]);
        });

        return $this->report;
    }

    private function lockGrades(AcademicPeriod $period, int $userId): void
    {
        // Bloquear todas las notas del período
        $count = Grade::whereHas('enrollmentItem.enrollment', fn ($q) =>
                $q->where('academic_period_id', $period->id)
            )
            ->where('locked', false)
            ->update([
                'locked'    => true,
                'locked_at' => now(),
                'locked_by' => $userId,
            ]);

        $this->report['locked_grades'] = $count;
    }

    private function calculateFinalGrades(AcademicPeriod $period): void
    {
        $items = EnrollmentItem::whereHas('enrollment', fn ($q) =>
                $q->where('academic_period_id', $period->id)
            )
            ->with([
                'grades.evaluationParameter',
                'enrollment',
            ])
            ->get();

        foreach ($items as $item) {
            try {
                // Obtener parámetros del período
                $parameters = \Modules\Academic\Models\EvaluationParameter::where('academic_period_id', $period->id)
                    ->where(fn ($q) =>
                        $q->where('curriculum_id', $item->curricula_id)
                          ->orWhereNull('curriculum_id')
                    )
                    ->where('active', true)
                    ->get();

                // Preferir parámetros específicos del currículo
                $specific = $parameters->where('curriculum_id', $item->curricula_id);
                $params   = $specific->isNotEmpty() ? $specific : $parameters->whereNull('curriculum_id');

                if ($params->isEmpty()) {
                    $this->report['errors'][] = "Item #{$item->id}: sin parámetros de evaluación.";
                    $this->report['incomplete_items']++;
                    continue;
                }

                // Calcular promedio ponderado
                $totalWeight = 0;
                $allGraded   = true;

                foreach ($params as $param) {
                    $grade = $item->grades->firstWhere('evaluation_parameter_id', $param->id);

                    if (! $grade) {
                        $allGraded = false;
                        break;
                    }

                    $totalWeight += ($grade->score * $param->percentage) / 100;
                }

                if (! $allGraded) {
                    // Items sin todas las notas → reprobado por inasistencia
                    $item->update([
                        'final_grade' => 0.00,
                        'status'      => 'reprobado',
                        'locked'      => true,
                    ]);
                    $this->report['failed_items']++;
                    continue;
                }

                $finalGrade = round($totalWeight, 2);
                $status     = $finalGrade >= 7.0 ? 'aprobado' : 'reprobado';

                $item->update([
                    'final_grade' => $finalGrade,
                    'status'      => $status,
                    'locked'      => true,
                ]);

                if ($status === 'aprobado') {
                    $this->report['approved_items']++;
                } else {
                    $this->report['failed_items']++;
                }

            } catch (\Exception $e) {
                $this->report['errors'][] = "Item #{$item->id}: {$e->getMessage()}";
            }
        }

        $this->report['locked_items'] = $items->count();
    }

    private function completeEnrollments(AcademicPeriod $period): void
    {
        $enrollments = Enrollment::where('academic_period_id', $period->id)
            ->whereIn('status', ['active', 'registered'])
            ->with('items')
            ->get();

        foreach ($enrollments as $enrollment) {
            $total    = $enrollment->items->count();
            $approved = $enrollment->items->where('status', 'aprobado')->count();

            // Determinar si el estudiante "promovió"
            // Criterio: aprobó al menos el 60% de las materias
            $promotionRate = $total > 0 ? ($approved / $total) : 0;

            $enrollment->update([
                'status' => 'completed',
            ]);

            $this->report['completed_enrollments']++;
        }
    }

    public function getNextSemesterSuggestion(int $studentId, int $careerId): ?int
    {
        // Obtener el semestre más alto que el estudiante tiene materias aprobadas
        $lastApprovedSemesterId = EnrollmentItem::whereHas('enrollment', fn ($q) =>
                $q->where('student_id', $studentId)
                  ->where('career_id', $careerId)
                  ->where('status', 'completed')
            )
            ->where('status', 'aprobado')
            ->with('curriculum.semester')
            ->get()
            ->max(fn ($item) => $item->curriculum->semester->number ?? 0);

        if (! $lastApprovedSemesterId) return null;

        return \Modules\Institucion\Models\Semester::where('career_id', $careerId)
            ->where('number', $lastApprovedSemesterId + 1)
            ->value('id');
    }
}