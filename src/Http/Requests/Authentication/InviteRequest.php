<?php

namespace VanDmade\Cuztomisable\Http\Requests\Authentication;

use VanDmade\Cuztomisable\Concerns\Validators\Phone as PhoneValidator;
use VanDmade\Cuztomisable\Http\Requests\CuztomisableRequest;

class InviteRequest extends CuztomisableRequest
{

    use PhoneValidator;

    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'use_phone' => filter_var($this->input('use_phone', false), FILTER_VALIDATE_BOOL),
            'name' => trim((string) $this->input('name')),
            'email' => strtolower(trim((string) $this->input('email'))),
        ]);
        $this->preparePhoneForValidation();
    }

    public function rules(): array
    {
        $params = [
            'name' => 'required',
            'use_phone' => 'nullable|boolean',
        ];
        return array_merge(
            $params,
            intval($this->input('use_phone')) == '1' ?
                $this->phoneValidationRules($this->input('country_code'), $this->input('phone')) :
                ['email' => 'required|email']
        );
    }

}
