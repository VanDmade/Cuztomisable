<?php

namespace VanDmade\Cuztomisable\Requests\Users;

use VanDmade\Cuztomisable\Requests\BaseRequest;

class RefreshRequest extends BaseRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'token' => trim((string) $this->input('token')),
        ]);
    }

    public function rules(): array
    {
        return [
            'token' => 'required',
        ];
    }
}
