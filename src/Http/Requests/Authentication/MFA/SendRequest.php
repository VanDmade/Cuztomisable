<?php

namespace VanDmade\Cuztomisable\Http\Requests\Authentication\MFA;

use VanDmade\Cuztomisable\Http\Requests\CuztomisableRequest;

class SendRequest extends CuztomisableRequest
{

    public function authorize(): bool
    {
        return config('cuztomisable.login.multi_factor_authentication.allowed', false);
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:email,phone,resend',
        ];
    }

}
