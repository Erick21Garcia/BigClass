<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Academic\Exports\GenericExport;
use Modules\Academic\Services\ExportService;

/**
 * Exportación de datos a Excel (.xlsx).
 *
 * Instalación de la librería (cuando se necesite activar):
 *   composer require maatwebsite/excel
 *   php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider" --tag=config
 *
 * Rutas sugeridas (web.php o api.php del módulo Academic):
 *
 *   Route::get('exports/students-by-section',   [ExportController::class, 'studentsBySection']);
 *   Route::get('exports/grade-report',          [ExportController::class, 'gradeReport']);
 *   Route::get('exports/kardex-by-career',      [ExportController::class, 'kardexByCareer']);
 *   Route::get('exports/enrollments-by-period', [ExportController::class, 'enrollmentsByPeriod']);
 *   Route::get('exports/schedule-by-teacher',   [ExportController::class, 'scheduleByTeacher']);
 *   Route::get('exports/attendance-report',     [ExportController::class, 'attendanceReport']);
 *   Route::get('exports/approval-stats',        [ExportController::class, 'approvalStats']);
 */
class ExportController extends Controller
{
    public function __construct(protected ExportService $exportService)
    {
    }

    public function studentsBySection(Request $request)
    {
        $request->validate([
            'section_id' => ['required', 'integer', 'exists:sections,id'],
        ]);

        $rows = $this->exportService->studentsBySection($request->integer('section_id'));
        $meta = $this->exportService->studentsBySectionMeta($request->integer('section_id'));

        return Excel::download(
            new GenericExport($rows, $meta['sheet']),
            $meta['filename'],
        );
    }

    public function gradeReport(Request $request)
    {
        $request->validate([
            'section_id'         => ['required', 'integer', 'exists:sections,id'],
            'academic_period_id' => ['required', 'integer', 'exists:academic_periods,id'],
        ]);

        $rows = $this->exportService->gradeReport(
            $request->integer('section_id'),
            $request->integer('academic_period_id'),
        );
        $meta = $this->exportService->gradeReportMeta(
            $request->integer('section_id'),
            $request->integer('academic_period_id'),
        );

        return Excel::download(
            new GenericExport($rows, $meta['sheet']),
            $meta['filename'],
        );
    }

    public function kardexByCareer(Request $request)
    {
        $request->validate([
            'career_id' => ['required', 'integer', 'exists:careers,id'],
        ]);

        $rows = $this->exportService->kardexByCareer($request->integer('career_id'));
        $meta = $this->exportService->kardexByCareerMeta($request->integer('career_id'));

        return Excel::download(
            new GenericExport($rows, $meta['sheet']),
            $meta['filename'],
        );
    }

    public function enrollmentsByPeriod(Request $request)
    {
        $request->validate([
            'academic_period_id' => ['required', 'integer', 'exists:academic_periods,id'],
        ]);

        $rows = $this->exportService->enrollmentsByPeriod($request->integer('academic_period_id'));
        $meta = $this->exportService->enrollmentsByPeriodMeta($request->integer('academic_period_id'));

        return Excel::download(
            new GenericExport($rows, $meta['sheet']),
            $meta['filename'],
        );
    }

    public function scheduleByTeacher(Request $request)
    {
        $request->validate([
            'teacher_id'         => ['required', 'integer', 'exists:teachers,id'],
            'academic_period_id' => ['required', 'integer', 'exists:academic_periods,id'],
        ]);

        $rows = $this->exportService->scheduleByTeacher(
            $request->integer('teacher_id'),
            $request->integer('academic_period_id'),
        );
        $meta = $this->exportService->scheduleByTeacherMeta(
            $request->integer('teacher_id'),
            $request->integer('academic_period_id'),
        );

        return Excel::download(
            new GenericExport($rows, $meta['sheet']),
            $meta['filename'],
        );
    }

    public function attendanceReport(Request $request)
    {
        $request->validate([
            'section_id' => ['required', 'integer', 'exists:sections,id'],
        ]);

        $rows = $this->exportService->attendanceReport($request->integer('section_id'));
        $meta = $this->exportService->attendanceReportMeta($request->integer('section_id'));

        return Excel::download(
            new GenericExport($rows, $meta['sheet']),
            $meta['filename'],
        );
    }

    public function approvalStats(Request $request)
    {
        $request->validate([
            'academic_period_id' => ['required', 'integer', 'exists:academic_periods,id'],
        ]);

        $rows = $this->exportService->approvalStats($request->integer('academic_period_id'));
        $meta = $this->exportService->approvalStatsMeta($request->integer('academic_period_id'));

        return Excel::download(
            new GenericExport($rows, $meta['sheet']),
            $meta['filename'],
        );
    }
}