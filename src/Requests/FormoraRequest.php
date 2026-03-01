<?php

namespace VanDmade\Cuztomisable\Requests;

use VanDmade\Cuztomisable\Requests\BaseRequest;

class FormoraRequest extends BaseRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'to' => 'required|string|max:255',
            'to_params' => 'nullable|string|max:65535',
            'current' => 'required|string|max:255',
            'current_params' => 'nullable|string|max:65535',
            'form' => 'nullable|string|max:65535',
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'to' => trim((string) $this->input('to')),
            'current' => trim((string) $this->input('current')),
        ]);
    }

}
