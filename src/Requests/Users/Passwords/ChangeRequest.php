<?php

namespace VanDmade\Cuztomisable\Requests\Users\Passwords;

use Illuminate\Foundation\Http\FormRequest;

class ChangeRequest extends FormRequest
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
            'new' => 'required',
            'current' => 'required',
        ];
    }
}
