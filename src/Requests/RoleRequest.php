<?php

namespace VanDmade\Cuztomisable\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Auth;

class RoleRequest extends FormRequest
{

    public function authorize(): bool
    {
        return Auth::check() ? Auth::user()->admin : false;
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
            'slug' => 'required|unique:roles,slug,'.$id.',id',
            'description' => 'nullable',
            'permissions' => 'nullable|array',
            'permissions.*' => 'nullable|exists:permissions,id',
        ];
    }
}
