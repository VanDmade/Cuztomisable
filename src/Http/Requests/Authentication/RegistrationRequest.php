<?php

namespace VanDmade\Cuztomisable\Http\Requests\Authentication;

use VanDmade\Cuztomisable\Concerns\Validators\Phone as PhoneValidator;
use VanDmade\Cuztomisable\Http\Requests\CuztomisableRequest;

class RegistrationRequest extends CuztomisableRequest
{

    use PhoneValidator;

    public function authorize(): bool
    {
        $platform = $this->header('X-App-Platform') === 'mobile' ? 'mobile' : 'web';
        return !config('cuztomisable.account.registration.disabled.'.$platform, false) ||
            !empty($this->route('code'));
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'username' => trim((string) $this->input('username')),
            'email' => strtolower(trim((string) $this->input('email'))),
            'timezone' => trim((string) $this->input('timezone')),
            'address' => trim((string) $this->input('address')),
            'address_two' => trim((string) $this->input('address_two')),
            'address_three' => trim((string) $this->input('address_three')),
            'city' => trim((string) $this->input('city')),
            'state_or_province' => trim((string) $this->input('state_or_province')),
            'zip_or_postal_code' => trim((string) $this->input('zip_or_postal_code')),
            'country' => trim((string) $this->input('country')),
        ]);
        $this->preparePhoneForValidation();
    }

    public function rules(): array
    {
        $params = array_merge([
            'name' => 'required',
            'username' => !config('cuztomisable.login.login_with.email', false) &&
                !config('cuztomisable.login.login_with.phone', false) ? 'required' : 'nullable',
            'email' => 'required|email',
            'password' => 'required',
            'timezone' => 'nullable',
        ], $this->phoneValidationRules($this->input('country_code'), $this->input('phone')));
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
