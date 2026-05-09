<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Modules\Academic\Models\AcademicPeriod;
use Modules\Academic\Models\Enrollment;
use Modules\Academic\Models\EnrollmentItem;
use Modules\Academic\Models\EvaluationParameter;
use Modules\Academic\Models\Grade;
use Modules\Academic\Models\Section;
use Modules\Institucion\Models\Curriculum;
use Modules\People\Models\Student;

class ReportController extends Controller
{
    // ── 1. Ficha de Matrícula ─────────────────────────────────────────────────
    public function enrollmentCard(Enrollment $enrollment)
    {
        $enrollment->load([
            'student.person',
            'career.faculty.institution',
            'semester',
            'academicPeriod',
            'items.curriculum.subject',
        ]);

        $pdf = Pdf::loadView('academic::reports.enrollment-card', [
            'enrollment' => $enrollment,
            'items'      => $enrollment->items->where('active', true),
            'generated'  => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'portrait');

        $filename = "matricula-{$enrollment->student->person->first_surname}-{$enrollment->id}.pdf";

        return $pdf->download($filename);
    }

    // ── 2. Boletín de Calificaciones ──────────────────────────────────────────
    public function gradeReport(Enrollment $enrollment)
    {
        $enrollment->load([
            'student.person',
            'career.faculty.institution',
            'semester',
            'academicPeriod',
            'items.curriculum.subject',
            'items.grades.evaluationParameter',
        ]);

        $academicPeriodId = $enrollment->academic_period_id;

        $parameters = EvaluationParameter::where('academic_period_id', $academicPeriodId)
            ->whereNull('curriculum_id')
            ->where('active', true)
            ->orderBy('is_final')
            ->orderBy('name')
            ->get();

        $items = $enrollment->items->where('active', true)->map(function ($item) use ($parameters) {
            $gradesByParam = $item->grades->keyBy('evaluation_parameter_id');

            return [
                'subject'     => $item->curriculum->subject->name,
                'code'        => $item->curriculum->subject->code,
                'credits'     => $item->curriculum->subject->credits,
                'final_grade' => $item->final_grade,
                'status'      => $item->status,
                'locked'      => $item->locked,
                'grades'      => $parameters->map(fn ($p) => [
                    'name'       => $p->name,
                    'percentage' => $p->percentage,
                    'score'      => isset($gradesByParam[$p->id])
                        ? (float) $gradesByParam[$p->id]->score
                        : null,
                ])->values()->all(),
            ];
        })->values();

        $pdf = Pdf::loadView('academic::reports.grade-report', [
            'enrollment' => $enrollment,
            'parameters' => $parameters,
            'items'      => $items,
            'generated'  => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'landscape');

        $filename = "boletin-{$enrollment->student->person->first_surname}-{$enrollment->id}.pdf";

        return $pdf->download($filename);
    }

    // ── 3. Acta de Calificaciones por Materia (para el docente) ──────────────
    public function subjectAct(Request $request)
    {
        $request->validate([
            'section_id'         => ['required', 'integer', 'exists:sections,id'],
            'academic_period_id' => ['required', 'integer', 'exists:academic_periods,id'],
        ]);

        $section = Section::with([
            'curriculum.subject',
            'curriculum.semester.career.faculty.institution',
            'teacher.person',
            'academicPeriod',
            'enrollmentItems' => fn ($q) => $q->where('active', true)
                ->with([
                    'enrollment.student.person',
                    'grades.evaluationParameter',
                ]),
        ])->findOrFail($request->section_id);

        $period = AcademicPeriod::findOrFail($request->academic_period_id);

        $parameters = EvaluationParameter::where('academic_period_id', $period->id)
            ->whereNull('curriculum_id')
            ->where('active', true)
            ->orderBy('is_final')
            ->orderBy('name')
            ->get();

        $rows = $section->enrollmentItems->map(function ($item) use ($parameters) {
            $gradesByParam = $item->grades->keyBy('evaluation_parameter_id');

            return [
                'enrollment_number' => $item->enrollment->student->enrollment_number,
                'full_name'         => $item->enrollment->student->person->full_name,
                'grades'            => $parameters->map(fn ($p) => [
                    'score' => isset($gradesByParam[$p->id])
                        ? (float) $gradesByParam[$p->id]->score
                        : null,
                ])->values()->all(),
                'final_grade' => $item->final_grade,
                'status'      => $item->status,
            ];
        })->sortBy('full_name')->values();

        $pdf = Pdf::loadView('academic::reports.subject-act', [
            'section'    => $section,
            'period'     => $period,
            'parameters' => $parameters,
            'rows'       => $rows,
            'generated'  => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'landscape');

        $subject  = $section->curriculum->subject->name;
        $filename = "acta-{$subject}-{$period->name}.pdf";

        return $pdf->download($filename);
    }

    // ── 4. Kardex del Estudiante ──────────────────────────────────────────────
    public function kardex(Student $student)
    {
        $student->load('person');

        $enrollments = Enrollment::where('student_id', $student->id)
            ->whereIn('status', ['completed', 'active'])
            ->with([
                'career.faculty.institution',
                'semester',
                'academicPeriod',
                'items' => fn ($q) => $q->where('active', true)
                    ->with('curriculum.subject'),
            ])
            ->orderBy('academic_period_id')
            ->get();

        // Agrupar por carrera → período
        $grouped = $enrollments->groupBy('career_id')->map(function ($careerEnrollments) {
            $career = $careerEnrollments->first()->career;

            $periods = $careerEnrollments->map(function ($enrollment) {
                $items    = $enrollment->items;
                $approved = $items->where('status', 'aprobado')->count();
                $total    = $items->count();

                return [
                    'period'      => $enrollment->academicPeriod->name,
                    'semester'    => $enrollment->semester->name,
                    'status'      => $enrollment->status,
                    'items'       => $items->map(fn ($item) => [
                        'subject'     => $item->curriculum->subject->name,
                        'code'        => $item->curriculum->subject->code,
                        'credits'     => $item->curriculum->subject->credits,
                        'final_grade' => $item->final_grade,
                        'status'      => $item->status,
                    ])->values()->all(),
                    'approved'    => $approved,
                    'total'       => $total,
                    'period_average' => $items->whereNotNull('final_grade')->avg('final_grade'),
                ];
            })->values()->all();

            $totalCredits   = $careerEnrollments->flatMap->items
                ->where('status', 'aprobado')
                ->sum('curriculum.subject.credits');

            $overallAverage = $careerEnrollments->flatMap->items
                ->whereNotNull('final_grade')
                ->avg('final_grade');

            return [
                'career'          => $career->name,
                'faculty'         => $career->faculty->name,
                'institution'     => $career->faculty->institution->name,
                'periods'         => $periods,
                'total_credits'   => $totalCredits,
                'overall_average' => $overallAverage ? round($overallAverage, 2) : null,
            ];
        })->values();

        $pdf = Pdf::loadView('academic::reports.kardex', [
            'student'   => $student,
            'grouped'   => $grouped,
            'generated' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'portrait');

        $filename = "kardex-{$student->person->first_surname}-{$student->id}.pdf";

        return $pdf->download($filename);
    }
}