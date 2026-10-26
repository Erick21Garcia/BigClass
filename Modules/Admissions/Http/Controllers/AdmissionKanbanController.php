<?php

namespace Modules\Admissions\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Academic\Models\Enrollment;
use Modules\Admissions\Http\Requests\MoveApplicantStatusRequest;
use Modules\Admissions\Models\Applicant;
use Modules\Admissions\Services\AdmissionReviewService;
use Modules\Institucion\Models\Semester;
use Modules\People\Models\Student;

class AdmissionKanbanController extends Controller
{
    public function __construct(
        private readonly AdmissionReviewService $reviewService
    ) {}

    public function index(Request $request): Response
    {
        // Solo staff: esta pantalla muestra datos y documentos de aspirantes.
        abort_unless($request->user()?->hasAnyRole('admin', 'super-admin'), 403);

        $applicants = Applicant::inKanban()
            ->with('career', 'documents')
            ->get();

        $approved = Applicant::where('status', 'aprobada')->with('career')->get()->keyBy('user_id');

        $pendingMatriculas = Student::with('person')
            ->whereHas('person', fn ($q) => $q->whereIn('user_id', $approved->keys()))
            ->whereNotIn('id', Enrollment::select('student_id'))
            ->get()
            ->map(function (Student $student) use ($approved) {
                $career = $approved->get($student->person->user_id)->career;
                $firstSemester = Semester::where('career_id', $career->id)->where('number', 1)->first();

                return [
                    'student_id'   => $student->id,
                    'student_name' => $student->person->full_name,
                    'career_name'  => $career->name,
                    'enroll_url'   => $firstSemester
                        ? "/enrollments/create?semester_id={$firstSemester->id}&student_id={$student->id}"
                        : null,
                ];
            })
            ->values();

        return Inertia::render('admissions/staff/Kanban', [
            'columns' => collect(['recibida', 'en_revision', 'aprobada', 'rechazada'])->map(fn ($status) => [
                'status' => $status,
                'applicants' => $applicants->where('status', $status)->map(fn (Applicant $a) => [
                    'id'           => $a->id,
                    'full_name'    => $a->full_name,
                    'career_name'  => $a->career->name,
                    'submitted_at' => $a->submitted_at,
                    'documents'    => $a->documents->map(fn ($d) => [
                        'type'          => $d->type,
                        'original_name' => $d->original_name,
                        'download_url'  => $d->downloadUrl(30),
                    ]),
                ])->values(),
            ]),
            'pending_matriculas' => $pendingMatriculas,
        ]);
    }

    public function move(MoveApplicantStatusRequest $request, Applicant $applicant): RedirectResponse
    {
        try {
            $this->reviewService->moveStatus(
                $applicant,
                $request->validated('status'),
                $request->validated('notes'),
                $request->user()->id
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        } catch (\Throwable $e) {
            // Error inesperado (BD, restricciones, etc.): se registra y se
            // le muestra al staff un mensaje genérico en vez de una página 500.
            report($e);
            return back()->withErrors(['status' => 'No se pudo completar la operación por un error inesperado. Revisa los registros del sistema.']);
        }

        return back()->with('success', 'Estado actualizado.');
    }
}