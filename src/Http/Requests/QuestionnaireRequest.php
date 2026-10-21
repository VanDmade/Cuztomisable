<?php

namespace VanDmade\Cuztomisable\Http\Requests;

use Illuminate\Support\Facades\Auth;

/**
 * Validates the shape of submitted questionnaire answers - keyed by question key. Each answer is checked against
 * its own question by QuestionnaireService, since the rules come from the questions in the database.
 */
class QuestionnaireRequest extends CuztomisableRequest
{

    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'answers' => ['required', 'array', 'min:1'],
        ];
    }

}
