<?php

namespace Modules\Academic\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfInstance;
use Modules\Academic\Models\AcademicPeriod;
use Modules\Academic\Models\Enrollment;
use Modules\Academic\Models\EvaluationParameter;
use Modules\Academic\Models\Section;
use Modules\People\Models\Student;

class ReportService
{

    public function generateEnrollmentCard(Enrollment $enrollment): PdfInstance
    {
        $enrollment->load([
            'student.person',
            'career.faculty.institution',
            'semester',
            'academicPeriod',
            'items.curriculum.subject',
        ]);

        return Pdf::loadView('academic::reports.enrollment-card', [
            'enrollment' => $enrollment,
            'items'      => $enrollment->items->where('active', true),
            'generated'  => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'portrait');
    }

    public function enrollmentCardFilename(Enrollment $enrollment): string
    {
        return "matricula-{$enrollment->student->person->first_surname}-{$enrollment->id}.pdf";
    }

    public function generateGradeReport(Enrollment $enrollment): PdfInstance
    {
        $enrollment->load([
            'student.person',
            'career.faculty.institution',
            'semester',
            'academicPeriod',
            'items.curriculum.subject',
            'items.grades.evaluationParameter',
        ]);

        $parameters = $this->getGlobalParameters($enrollment->academic_period_id);

        $items = $enrollment->items->where('active', true)->map(function ($item) use ($parameters) {
            return $this->buildItemGradeRow($item, $parameters);
        })->values();

        return Pdf::loadView('academic::reports.grade-report', [
            'enrollment' => $enrollment,
            'parameters' => $parameters,
            'items'      => $items,
            'generated'  => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'landscape');
    }

    public function gradeReportFilename(Enrollment $enrollment): string
    {
        return "boletin-{$enrollment->student->person->first_surname}-{$enrollment->id}.pdf";
    }

    public function generateSubjectAct(int $sectionId, int $academicPeriodId): PdfInstance
    {
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
        ])->findOrFail($sectionId);

        $period     = AcademicPeriod::findOrFail($academicPeriodId);
        $parameters = $this->getGlobalParameters($period->id);

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

        return Pdf::loadView('academic::reports.subject-act', [
            'section'    => $section,
            'period'     => $period,
            'parameters' => $parameters,
            'rows'       => $rows,
            'generated'  => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'landscape');
    }

    public function subjectActFilename(int $sectionId, int $academicPeriodId): string
    {
        $section = Section::with('curriculum.subject')->findOrFail($sectionId);
        $period  = AcademicPeriod::findOrFail($academicPeriodId);

        return "acta-{$section->curriculum->subject->name}-{$period->name}.pdf";
    }

    public function generateKardex(Student $student): PdfInstance
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

        $grouped = $this->groupEnrollmentsByCareer($enrollments);

        return Pdf::loadView('academic::reports.kardex', [
            'student'   => $student,
            'grouped'   => $grouped,
            'generated' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'portrait');
    }

    public function kardexFilename(Student $student): string
    {
        return "kardex-{$student->person->first_surname}-{$student->id}.pdf";
    }

    private function getGlobalParameters(int $academicPeriodId)
    {
        return EvaluationParameter::where('academic_period_id', $academicPeriodId)
            ->whereNull('curriculum_id')
            ->where('active', true)
            ->orderBy('is_final')
            ->orderBy('name')
            ->get();
    }

    private function buildItemGradeRow($item, $parameters): array
    {
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
    }

    private function groupEnrollmentsByCareer($enrollments)
    {
        return $enrollments->groupBy('career_id')->map(function ($careerEnrollments) {
            $career = $careerEnrollments->first()->career;

            $periods = $careerEnrollments->map(function ($enrollment) {
                $items    = $enrollment->items;
                $approved = $items->where('status', 'aprobado')->count();

                return [
                    'period'         => $enrollment->academicPeriod->name,
                    'semester'       => $enrollment->semester->name,
                    'status'         => $enrollment->status,
                    'items'          => $items->map(fn ($item) => [
                        'subject'     => $item->curriculum->subject->name,
                        'code'        => $item->curriculum->subject->code,
                        'credits'     => $item->curriculum->subject->credits,
                        'final_grade' => $item->final_grade,
                        'status'      => $item->status,
                    ])->values()->all(),
                    'approved'       => $approved,
                    'total'          => $items->count(),
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
    }
}