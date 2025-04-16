<?php

namespace VanDmade\Cuztomisable\Requests\Authentication;

use Illuminate\Foundation\Http\FormRequest;

class RegistrationRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'required' => __('cuztomisable/global.form.required'),
            'email' => __('cuztomisable/global.form.email'),
            'in' => __('cuztomisable/global.form.in'),
        ];
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required',
            'middle_name' => 'nullable',
            'last_name' => 'required',
            'suffix' => 'nullable',
            'title' => 'nullable',
            'username' => 'nullable',
            'email' => 'required|email',
            'password' => 'required',
            'gender' => 'nullable',
            'timezone' => 'nullable',
            'phones' => 'nullable',
            'phones.*' => 'nullable|array',
            'phones.*.number' => 'required',
            'phones.*.country_code' => 'required',
            'phones.*.extension' => 'nullable',
            'phones.*.mobile' => 'nullable|in:0,1',
            'phones.*.default' => 'nullable|in:0,1',
            'addresses' => 'nullable',
            'addresses.*' => 'nullable|array',
            'addresses.*.address' => 'required',
            'addresses.*.address_two' => 'nullable',
            'addresses.*.address_three' => 'nullable',
            'addresses.*.state_or_province' => 'required',
            'addresses.*.city' => 'required',
            'addresses.*.country' => 'required',
            'addresses.*.zip_or_postal_code' => 'required',
            'addresses.*.shipping' => 'required|in:0,1',
            'addresses.*.billing' => 'required|in:0,1',
        ];
    }

}
