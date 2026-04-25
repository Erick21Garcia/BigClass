<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Modules\Academic\Http\Requests\StoreAcademicPeriodRequest;
use Modules\Academic\Http\Requests\UpdateAcademicPeriodRequest;
use Modules\Academic\Models\AcademicPeriod;
use Modules\Academic\Models\EvaluationParameter;
use Modules\Academic\Services\AcademicPeriodService;
use Modules\Institucion\Models\Curriculum;

class AcademicPeriodController extends Controller
{
    public function __construct(
        private AcademicPeriodService $academicPeriodService
    ) {}

    public function index()
    {
        $academicPeriods = AcademicPeriod::query()
            ->orderBy('start_date', 'desc')
            ->get()
            ->map(fn ($period) => [
                'id'         => $period->id,
                'name'       => $period->name,
                'start_date' => $period->start_date?->format('Y-m-d'),
                'end_date'   => $period->end_date?->format('Y-m-d'),
                'is_active'  => $period->is_active,
                'status'     => $period->status,
                'created_at' => $period->created_at->format('Y-m-d H:i'),
            ]);

        return Inertia::render('academic-periods/Index', compact('academicPeriods'));
    }

    public function parameters(AcademicPeriod $academicPeriod)
    {
        $parameters = EvaluationParameter::where('academic_period_id', $academicPeriod->id)
            ->whereNull('curriculum_id')
            ->where('active', true)
            ->orderBy('created_at', 'asc')
            ->get(['id', 'name', 'percentage', 'is_final']);

        return response()->json($parameters);
    }

    public function show(AcademicPeriod $academicPeriod)
    {
        $globalParameters = EvaluationParameter::where('academic_period_id', $academicPeriod->id)
            ->whereNull('curriculum_id')
            ->where('active', true)
            ->orderBy('is_final')
            ->orderBy('name')
            ->get()
            ->map(fn ($p) => [
                'id'          => $p->id,
                'name'        => $p->name,
                'percentage'  => $p->percentage,
                'is_final'    => $p->is_final,
                'curriculum'  => null,
            ]);

        $specificParameters = EvaluationParameter::where('academic_period_id', $academicPeriod->id)
            ->whereNotNull('curriculum_id')
            ->where('active', true)
            ->with('curriculum.subject', 'curriculum.semester')
            ->orderBy('curriculum_id')
            ->orderBy('is_final')
            ->orderBy('name')
            ->get()
            ->map(fn ($p) => [
                'id'         => $p->id,
                'name'       => $p->name,
                'percentage' => $p->percentage,
                'is_final'   => $p->is_final,
                'curriculum' => [
                    'id'      => $p->curriculum->id,
                    'subject' => $p->curriculum->subject->name,
                    'semester' => $p->curriculum->semester->name,
                ],
            ]);

        $globalTotal = $globalParameters->sum('percentage');

        return Inertia::render('academic-periods/Show', [
            'academicPeriod' => [
                'id'         => $academicPeriod->id,
                'name'       => $academicPeriod->name,
                'start_date' => $academicPeriod->start_date?->format('Y-m-d'),
                'end_date'   => $academicPeriod->end_date?->format('Y-m-d'),
                'is_active'  => $academicPeriod->is_active,
                'status'     => $academicPeriod->status,
            ],
            'globalParameters'   => $globalParameters,
            'specificParameters' => $specificParameters,
            'globalTotal'        => (float) $globalTotal,
        ]);
    }

    public function store(StoreAcademicPeriodRequest $request)
    {
        $this->academicPeriodService->create($request->validated());

        return redirect()
            ->route('academic-periods.index')
            ->with('success', 'Período académico creado exitosamente');
    }

    public function edit(AcademicPeriod $academicPeriod)
    {
        return Inertia::render('academic-periods/Edit', [
            'academicPeriod' => [
                'id'         => $academicPeriod->id,
                'name'       => $academicPeriod->name,
                'start_date' => $academicPeriod->start_date?->format('Y-m-d'),
                'end_date'   => $academicPeriod->end_date?->format('Y-m-d'),
                'is_active'  => $academicPeriod->is_active,
                'status'     => $academicPeriod->status,
            ],
        ]);
    }

    public function update(UpdateAcademicPeriodRequest $request, AcademicPeriod $academicPeriod)
    {
        $this->academicPeriodService->update($academicPeriod, $request->validated());

        return redirect()
            ->route('academic-periods.index')
            ->with('success', 'Período académico actualizado exitosamente');
    }

    public function destroy(AcademicPeriod $academicPeriod)
    {
        try {
            $this->academicPeriodService->delete($academicPeriod);
        } catch (\DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('academic-periods.index')
            ->with('success', 'Período académico eliminado exitosamente');
    }
}