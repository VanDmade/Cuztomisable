<?php

namespace VanDmade\Cuztomisable\Requests\Authentication\MFA;

use VanDmade\Cuztomisable\Requests\BaseRequest;

class SendRequest extends BaseRequest
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
