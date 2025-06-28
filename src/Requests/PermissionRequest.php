<?php

namespace VanDmade\Cuztomisable\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Auth;

class PermissionRequest extends FormRequest
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
            'slug' => 'required|unique:permissions,slug,id,'.$id,
            'description' => 'nullable',
        ];
    }
}
