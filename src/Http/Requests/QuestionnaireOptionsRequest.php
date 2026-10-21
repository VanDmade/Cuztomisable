<?php

namespace VanDmade\Cuztomisable\Http\Requests;

use Illuminate\Support\Facades\Auth;

/**
 * Validates a search through a questionnaire question's choices.
 */
class QuestionnaireOptionsRequest extends CuztomisableRequest
{

    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }

}
