<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Academic\Http\Requests\StoreEvaluationParameterRequest;
use Modules\Academic\Http\Requests\UpdateEvaluationParameterRequest;
use Modules\Academic\Models\EvaluationParameter;
use Modules\Academic\Services\EvaluationParameterService;

class EvaluationParameterController extends Controller
{
    public function __construct(
        private EvaluationParameterService $service
    ) {}

    public function store(StoreEvaluationParameterRequest $request)
    {
        try {
            $this->service->create($request->validated());
        } catch (\DomainException $e) {
            return back()->withErrors(['percentage' => $e->getMessage()]);
        }

        return back()->with('success', 'Parámetro de evaluación creado exitosamente.');
    }

    public function update(UpdateEvaluationParameterRequest $request, EvaluationParameter $evaluationParameter)
    {
        try {
            $this->service->update($evaluationParameter, $request->validated());
        } catch (\DomainException $e) {
            return back()->withErrors(['percentage' => $e->getMessage()]);
        }

        return back()->with('success', 'Parámetro actualizado exitosamente.');
    }

    public function destroy(EvaluationParameter $evaluationParameter)
    {
        try {
            $this->service->delete($evaluationParameter);
        } catch (\DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return back()->with('success', 'Parámetro eliminado exitosamente.');
    }
}