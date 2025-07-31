<?php

namespace VanDmade\Cuztomisable\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
            'boolean' => __('cuztomisable/global.form.boolean'),
            'size' => __('cuztomisable/global.form.phone.size'),
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $emailInUse = config('auth.providers.users.model')::where('email', $this->email)
                ->where(function($query) {
                    if ($this->route('id')) {
                        $query->where('id', '!=', $this->route('id'));
                    }
                })
                ->exists();
            if ($emailInUse) {
                $validator->errors()->add('email', __('cuztomisable/user.errors.email_in_use'));
            }
        });
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'phone' => strval(cleanPhone($this->input('phone'))),
        ]);
    }

    public function rules(): array
    {
        $list = config('cuztomisable.locations.country_codes') ?? [];
        // Gets the designated size of the specific phone number
        foreach ($list as $i => $row) {
            if ($row['value'] == $this->input('country_code', 1)) {
                $size = $row['required_length'] ?? 10;
            }
        }
        $requirePhone = !config('cuztomisable.login.login_with.email', false) ||
            config('cuztomisable.login.login_with.phone', false) ||
            $this->input('phone') != '' ? 'required' : 'nullable';
        $params = [
            'name' => 'required',
            'username' => !config('cuztomisable.login.login_with.email', false) &&
                !config('cuztomisable.login.login_with.phone', false) ? 'required' : 'nullable',
            'email' => 'required|email',
            'timezone' => 'nullable',
            'phone' => $requirePhone.'|size:'.($size ?? 10),
            'country_code' => $requirePhone,
            'mfa' => 'nullable|in:0,1',
        ];
        // Determines if the address should be required, allowed, or straight up rejected
        if (($address = config('cuztomisable.account.address', false)) != false) {
            // If it's not required then it'll be dependant on whether any of it is filled out
            $required = $address['required'] || $this->input('address') != '' ||
                $this->input('city') != '' || $this->input('state_or_province') != '' ||
                $this->input('zip_or_postal_code') != '' ? 'required' : 'nullable';
            $params = array_merge($params, [
                'address' => $required,
                'address_two' => 'nullable',
                'address_three' => 'nullable',
                'city' => $required,
                'state_or_province' => $required,
                'zip_or_postal_code' => $required,
                'country' => $required,
            ]);
        }
        return $params;
    }

}
