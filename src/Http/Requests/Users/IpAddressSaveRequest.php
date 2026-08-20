<?php

namespace VanDmade\Cuztomisable\Http\Requests\Users;

use VanDmade\Cuztomisable\Http\Requests\CuztomisableRequest;

class IpAddressSaveRequest extends CuztomisableRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => 'nullable|string|max:255',
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'label' => trim((string) $this->input('label')),
        ]);
    }

}
