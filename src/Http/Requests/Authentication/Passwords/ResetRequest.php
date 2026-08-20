<?php

namespace VanDmade\Cuztomisable\Http\Requests\Authentication\Passwords;

use VanDmade\Cuztomisable\Http\Requests\CuztomisableRequest;

class ResetRequest extends CuztomisableRequest
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
