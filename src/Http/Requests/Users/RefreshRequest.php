<?php

namespace VanDmade\Cuztomisable\Http\Requests\Users;

use VanDmade\Cuztomisable\Http\Requests\CuztomisableRequest;

/**
 * Validation rules for refreshing an auth/session token.
 */
class RefreshRequest extends CuztomisableRequest
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
