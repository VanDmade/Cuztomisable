<?php

namespace VanDmade\Cuztomisable\Http\Requests;

/**
 * Validates a new order for the questionnaire's questions - every question id, first to last.
 */
class QuestionnaireOrderRequest extends CuztomisableRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:questionnaire_questions,id'],
        ];
    }

}
