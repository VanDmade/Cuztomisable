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
        $params = [
            'new' => 'required',
            'current' => 'required',
        ];
        if ($this->has('force')) {
            unset($params['current']);
            $this->merge(['force' => true]);
            $params['force'] = 'required|boolean';
        }
        return $params;
    }
}
