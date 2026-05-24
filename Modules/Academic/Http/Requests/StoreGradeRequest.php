<?php

namespace Modules\Academic\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGradeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'enrollment_item_id'      => ['required', 'integer', 'exists:enrollment_items,id'],
            'evaluation_parameter_id' => ['required', 'integer', 'exists:evaluation_parameters,id'],
            'score'                   => ['required', 'numeric', 'min:0', 'max:10'],
            'observations'            => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'enrollment_item_id.required'      => 'El ítem de matrícula es obligatorio.',
            'evaluation_parameter_id.required' => 'El parámetro de evaluación es obligatorio.',
            'score.required'                   => 'La nota es obligatoria.',
            'score.min'                        => 'La nota mínima es 0.',
            'score.max'                        => 'La nota máxima es 10.',
        ];
    }
}