<?php

namespace VanDmade\Cuztomisable\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared base FormRequest every Cuztomisable request extends - common validation messages.
 * Renamed from BaseRequest to match the CuztomisableController naming convention.
 */
class CuztomisableRequest extends FormRequest
{

    public function messages(): array
    {
        return [
            'required' => __('cuztomisable/global.form.required'),
            'email' => __('cuztomisable/global.form.email'),
            'exists' => __('cuztomisable/global.form.exists'),
            'in' => __('cuztomisable/global.form.in'),
            'boolean' => __('cuztomisable/global.form.boolean'),
            'unique' => __('cuztomisable/global.form.unique'),
            'string' => __('cuztomisable/global.form.string'),
            'min' => __('cuztomisable/global.form.min'),
            'max' => __('cuztomisable/global.form.max'),
            'size' => __('cuztomisable/global.form.phone.size'),
            'image' => __('cuztomisable/global.form.image'),
            'mimes' => __('cuztomisable/global.form.mimes'),
        ];
    }

}
