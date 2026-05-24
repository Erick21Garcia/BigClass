<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Academic\Http\Requests\SubjectActRequest;
use Modules\Academic\Models\Enrollment;
use Modules\Academic\Services\ReportService;
use Modules\People\Models\Student;

class ReportController extends Controller
{
    public function __construct(protected ReportService $reportService)
    {
    }

    public function enrollmentCard(Enrollment $enrollment)
    {
        $pdf      = $this->reportService->generateEnrollmentCard($enrollment);
        $filename = $this->reportService->enrollmentCardFilename($enrollment);

        return $pdf->download($filename);
    }

    public function gradeReport(Enrollment $enrollment)
    {
        $pdf      = $this->reportService->generateGradeReport($enrollment);
        $filename = $this->reportService->gradeReportFilename($enrollment);

        return $pdf->download($filename);
    }

    public function subjectAct(SubjectActRequest $request)
    {
        $pdf      = $this->reportService->generateSubjectAct(
            $request->integer('section_id'),
            $request->integer('academic_period_id'),
        );
        $filename = $this->reportService->subjectActFilename(
            $request->integer('section_id'),
            $request->integer('academic_period_id'),
        );

        return $pdf->download($filename);
    }

    public function kardex(Student $student)
    {
        $pdf      = $this->reportService->generateKardex($student);
        $filename = $this->reportService->kardexFilename($student);

        return $pdf->download($filename);
    }
}