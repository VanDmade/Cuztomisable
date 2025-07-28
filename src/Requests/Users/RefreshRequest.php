<?php

namespace VanDmade\Cuztomisable\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;

class RefreshRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'required' => __('cuztomisable/global.form.required'),
        ];
    }

    public function rules(): array
    {
        return [
            'token' => 'required',
        ];
    }
}
