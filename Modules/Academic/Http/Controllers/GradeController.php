<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Academic\Models\AcademicPeriod;
use Modules\Academic\Models\Enrollment;
use Modules\Academic\Models\EnrollmentItem;
use Modules\Academic\Models\EvaluationParameter;
use Modules\Academic\Models\Grade;
use Modules\Institucion\Models\Curriculum;

class GradeController extends Controller
{
    public function index()
    {
        $curriculumId     = request()->integer('curriculum_id');
        $academicPeriodId = request()->integer('academic_period_id');

        abort_if(! $curriculumId || ! $academicPeriodId, 400, 'Se requiere curriculum_id y academic_period_id.');

        $curriculum    = Curriculum::with('subject', 'semester.career.faculty.institution')->findOrFail($curriculumId);
        $academicPeriod = AcademicPeriod::findOrFail($academicPeriodId);

        $parameters = EvaluationParameter::where('academic_period_id', $academicPeriodId)
            ->where('curriculum_id', $curriculumId)
            ->where('active', true)
            ->get();

        if ($parameters->isEmpty()) {
            $parameters = EvaluationParameter::where('academic_period_id', $academicPeriodId)
                ->whereNull('curriculum_id')
                ->where('active', true)
                ->get();
        }

        $parameters = $parameters->sortBy('is_final')->sortBy('name')->values();

        $enrollmentItems = EnrollmentItem::where('curricula_id', $curriculumId)
            ->whereHas('enrollment', fn ($q) =>
                $q->where('academic_period_id', $academicPeriodId)
                  ->whereIn('status', ['active', 'registered'])
            )
            ->with([
                'enrollment.student.person',
                'grades.evaluationParameter',
            ])
            ->get();

        $rows = $enrollmentItems->map(function ($item) use ($parameters) {
            $gradesByParam = $item->grades->keyBy('evaluation_parameter_id');

            return [
                'enrollment_item_id' => $item->id,
                'status'             => $item->status,
                'final_grade'        => $item->final_grade,
                'student' => [
                    'id'                => $item->enrollment->student->id,
                    'full_name'         => $item->enrollment->student->person->full_name,
                    'enrollment_number' => $item->enrollment->student->enrollment_number,
                ],
                'grades' => $parameters->map(fn ($param) => [
                    'evaluation_parameter_id' => $param->id,
                    'grade_id'                => $gradesByParam[$param->id]->id ?? null,
                    'score'                   => $gradesByParam[$param->id]->score ?? null,
                    'observations'            => $gradesByParam[$param->id]->observations ?? null,
                ])->values()->all(),
            ];
        })->values()->all();

        $parameterTotal = $parameters->sum('percentage');

        return Inertia::render('grades/Index', [
            'curriculum' => [
                'id'   => $curriculum->id,
                'subject' => [
                    'id'   => $curriculum->subject->id,
                    'name' => $curriculum->subject->name,
                    'code' => $curriculum->subject->code,
                ],
                'semester' => [
                    'id'   => $curriculum->semester->id,
                    'name' => $curriculum->semester->name,
                    'career' => [
                        'id'   => $curriculum->semester->career->id,
                        'name' => $curriculum->semester->career->name,
                    ],
                ],
            ],
            'academicPeriod' => [
                'id'   => $academicPeriod->id,
                'name' => $academicPeriod->name,
            ],
            'parameters'     => $parameters->map(fn ($p) => [
                'id'         => $p->id,
                'name'       => $p->name,
                'percentage' => $p->percentage,
                'is_final'   => $p->is_final,
            ])->values()->all(),
            'parameterTotal' => (float) $parameterTotal,
            'rows'           => $rows,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'enrollment_item_id'      => ['required', 'integer', 'exists:enrollment_items,id'],
            'evaluation_parameter_id' => ['required', 'integer', 'exists:evaluation_parameters,id'],
            'score'                   => ['required', 'numeric', 'min:0', 'max:10'],
            'observations'            => ['nullable', 'string', 'max:500'],
        ], [
            'enrollment_item_id.required'      => 'El ítem de matrícula es obligatorio.',
            'evaluation_parameter_id.required' => 'El parámetro de evaluación es obligatorio.',
            'score.required'                   => 'La nota es obligatoria.',
            'score.min'                        => 'La nota mínima es 0.',
            'score.max'                        => 'La nota máxima es 10.',
        ]);

        $grade = Grade::updateOrCreate(
            [
                'enrollment_item_id'      => $validated['enrollment_item_id'],
                'evaluation_parameter_id' => $validated['evaluation_parameter_id'],
            ],
            [
                'score'        => $validated['score'],
                'observations' => $validated['observations'] ?? null,
                'active'       => true,
            ]
        );

        $this->recalculateFinalGrade($grade->enrollmentItem);

        return response()->json(['success' => true]);

        return redirect()->back()->with('success', 'Nota guardada exitosamente.');

    }

    public function destroy(Grade $grade)
    {
        $enrollmentItem = $grade->enrollmentItem;
        $grade->delete();

        $this->recalculateFinalGrade($enrollmentItem);

        return redirect()->back()->with('success', 'Nota eliminada exitosamente.');
    }

    private function recalculateFinalGrade(EnrollmentItem $item): void
    {
        $grades = Grade::where('enrollment_item_id', $item->id)
            ->where('active', true)
            ->with('evaluationParameter')
            ->get();

        if ($grades->isEmpty()) {
            $item->update(['final_grade' => null]);
            return;
        }

        $parameters = EvaluationParameter::forEnrollmentItem($item);

        if ($grades->count() < $parameters->count()) {
            return;
        }

        $weighted = $grades->sum(fn ($grade) =>
            ($grade->score * $grade->evaluationParameter->percentage) / 100
        );

        $finalGrade = round($weighted, 2);

        $status = $finalGrade >= 7 ? 'aprobado' : 'reprobado';

        $item->update([
            'final_grade' => $finalGrade,
            'status'      => $status,
        ]);
    }
}