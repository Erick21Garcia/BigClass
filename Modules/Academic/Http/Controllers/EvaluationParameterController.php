<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Academic\Models\AcademicPeriod;
use Modules\Academic\Models\EvaluationParameter;

class EvaluationParameterController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'academic_period_id' => ['required', 'integer', 'exists:academic_periods,id'],
            'curriculum_id'      => ['nullable', 'integer', 'exists:curricula,id'],
            'name'               => ['required', 'string', 'max:100'],
            'percentage'         => ['required', 'numeric', 'min:0.01', 'max:100'],
            'is_final'           => ['boolean'],
        ], [
            'academic_period_id.required' => 'El período académico es obligatorio.',
            'academic_period_id.exists'   => 'El período académico no existe.',
            'curriculum_id.exists'        => 'La materia seleccionada no existe.',
            'name.required'               => 'El nombre del parámetro es obligatorio.',
            'percentage.required'         => 'El porcentaje es obligatorio.',
            'percentage.min'              => 'El porcentaje debe ser mayor a 0.',
            'percentage.max'              => 'El porcentaje no puede superar 100.',
        ]);

        $currentTotal = EvaluationParameter::where('academic_period_id', $validated['academic_period_id'])
            ->where('curriculum_id', $validated['curriculum_id'] ?? null)
            ->where('active', true)
            ->sum('percentage');

        if (($currentTotal + $validated['percentage']) > 100) {
            $available = 100 - $currentTotal;
            return back()->withErrors([
                'percentage' => "El porcentaje excede el 100%. Solo puedes asignar hasta {$available}%.",
            ]);
        }

        EvaluationParameter::create([
            ...$validated,
            'active' => true,
        ]);

        return redirect()->back()->with('success', 'Parámetro de evaluación creado exitosamente.');
    }

    public function update(Request $request, EvaluationParameter $evaluationParameter)
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:100'],
            'percentage' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'is_final'   => ['boolean'],
        ], [
            'name.required'       => 'El nombre del parámetro es obligatorio.',
            'percentage.required' => 'El porcentaje es obligatorio.',
            'percentage.min'      => 'El porcentaje debe ser mayor a 0.',
            'percentage.max'      => 'El porcentaje no puede superar 100.',
        ]);

        $currentTotal = EvaluationParameter::where('academic_period_id', $evaluationParameter->academic_period_id)
            ->where('curriculum_id', $evaluationParameter->curriculum_id)
            ->where('active', true)
            ->where('id', '!=', $evaluationParameter->id)
            ->sum('percentage');

        if (($currentTotal + $validated['percentage']) > 100) {
            $available = 100 - $currentTotal;
            return back()->withErrors([
                'percentage' => "El porcentaje excede el 100%. Solo puedes asignar hasta {$available}%.",
            ]);
        }

        $evaluationParameter->update($validated);

        return redirect()->back()->with('success', 'Parámetro actualizado exitosamente.');
    }

    public function destroy(EvaluationParameter $evaluationParameter)
    {
        if ($evaluationParameter->grades()->exists()) {
            return back()->withErrors([
                'error' => 'No se puede eliminar el parámetro porque ya tiene notas registradas.',
            ]);
        }

        $evaluationParameter->delete();

        return redirect()->back()->with('success', 'Parámetro eliminado exitosamente.');
    }
}