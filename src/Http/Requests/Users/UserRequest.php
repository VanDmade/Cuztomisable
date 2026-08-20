<?php

namespace VanDmade\Cuztomisable\Http\Requests\Users;

use Illuminate\Validation\Validator;
use VanDmade\Cuztomisable\Http\Requests\CuztomisableRequest;

/**
 * Validation rules for creating/updating a user.
 */
class UserRequest extends CuztomisableRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $excludeIds = array_filter([
                auth()->id(),
                $this->routeIs('users.update') ? (int) $this->route('id') : null,
            ]);
            $emailInUse = config('auth.providers.users.model')::query()
                ->where('email', $this->email)
                ->whereNotIn('id', $excludeIds)
                ->exists();
            if ($emailInUse) {
                $validator->errors()->add('email', __('cuztomisable/user.errors.email_in_use'));
            }
        });
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'username' => trim((string) $this->input('username')),
            'email' => strtolower(trim((string) $this->input('email'))),
            'timezone' => trim((string) $this->input('timezone')),
            'phone' => strval(cleanPhone($this->input('phone'))),
            'address' => trim((string) $this->input('address')),
            'address_two' => trim((string) $this->input('address_two')),
            'address_three' => trim((string) $this->input('address_three')),
            'city' => trim((string) $this->input('city')),
            'state_or_province' => trim((string) $this->input('state_or_province')),
            'zip_or_postal_code' => trim((string) $this->input('zip_or_postal_code')),
            'country' => trim((string) $this->input('country')),
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
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp,heic|max:10240',
            'clear_image' => 'nullable|in:0,1',
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
