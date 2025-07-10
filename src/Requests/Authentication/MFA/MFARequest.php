<?php

namespace VanDmade\Cuztomisable\Requests\Authentication\MFA;

use Illuminate\Foundation\Http\FormRequest;

class MFARequest extends FormRequest
{

    public function authorize(): bool
    {
        return config('cuztomisable.login.multi_factor_authentication.enabled', false);
    }

    public function messages(): array
    {
        return [
            'required' => __('cuztomisable/global.form.required'),
            'in' => __('cuztomisable/global.form.in'),
        ];
    }

    public function rules(): array
    {
        return [
            'code' => 'required',
            'remember' => 'required|in:0,1',
        ];
    }

}
