<?php

namespace VanDmade\Cuztomisable\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FormoraRequest extends FormRequest
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
            'to' => 'required',
            'to_params' => 'nullable',
            'current' => 'required',
            'current_params' => 'nullable',
            'form' => 'nullable',
        ];
    }

}
