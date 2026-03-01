<?php

namespace VanDmade\Cuztomisable\Requests\Authentication\Passwords;

use VanDmade\Cuztomisable\Requests\BaseRequest;

class ResetRequest extends BaseRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required',
            'password' => 'required',
        ];
    }

}
