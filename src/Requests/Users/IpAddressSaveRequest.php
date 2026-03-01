<?php

namespace VanDmade\Cuztomisable\Requests\Users;

use VanDmade\Cuztomisable\Requests\BaseRequest;

class IpAddressSaveRequest extends BaseRequest
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
