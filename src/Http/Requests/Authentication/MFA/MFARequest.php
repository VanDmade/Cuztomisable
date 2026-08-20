<?php

namespace VanDmade\Cuztomisable\Http\Requests\Authentication\MFA;

use VanDmade\Cuztomisable\Http\Requests\CuztomisableRequest;

class MFARequest extends CuztomisableRequest
{

    public function authorize(): bool
    {
        return config('cuztomisable.login.multi_factor_authentication.allowed', false);
    }

    public function prepareForValidation(): void
    {
        if ($this->missing('remember')) {
            $this->merge([
                'remember' => '0',
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'code' => 'required',
            'remember' => 'required|in:0,1',
        ];
    }

}
