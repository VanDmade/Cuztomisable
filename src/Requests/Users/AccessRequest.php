<?php

namespace VanDmade\Cuztomisable\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;

class AccessRequest extends FormRequest
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
            'roles' => 'nullable|array',
            'roles.*' => 'nullable|exists:roles,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'nullable|exists:permissions,id',
        ];
    }
}
