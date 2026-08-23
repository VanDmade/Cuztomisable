<?php

namespace VanDmade\Cuztomisable\Http\Requests;

class TermsRequest extends CuztomisableRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'version' => 'required|string|max:255|unique:terms_and_conditions,version',
            'content' => 'required|string',
            'requires_reacceptance' => 'nullable|in:0,1',
        ];
    }

}
