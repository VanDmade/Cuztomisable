<?php

namespace VanDmade\Cuztomisable\Requests\Authentication;

use Illuminate\Foundation\Http\FormRequest;
use VanDmade\Cuztomisable\Traits\Validators\Phone as PhoneValidator;

class InviteRequest extends FormRequest
{

    use PhoneValidator;

    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'required' => __('cuztomisable/global.form.required'),
            'email' => __('cuztomisable/global.form.email'),
            'size' => __('cuztomisable/global.form.phone.size'),
        ];
    }

    public function prepareForValidation(): void
    {
        $this->preparePhoneForValidation();
    }

    public function rules(): array
    {
        $params = [
            'name' => 'required',
        ];
        return array_merge(
            $params,
            intval($this->input('use_phone')) == '1' ?
                $this->phoneValidationRules($this->input('country_code'), $this->input('phone')) :
                ['email' => 'required|email']
        );
    }

}
