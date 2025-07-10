<?php

namespace VanDmade\Cuztomisable\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PermissionRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'required' => __('cuztomisable/global.form.required'),
            'unique' => __('cuztomisable/global.form.unique'),
        ];
    }

    public function rules(): array
    {
        $id = $this->route('id') ?? null;
        return [
            'name' => 'required',
            'slug' => 'required|unique:permissions,slug,'.$id.',id',
            'description' => 'nullable',
        ];
    }
}
