<?php

namespace VanDmade\Cuztomisable\Http\Requests\Users;

use VanDmade\Cuztomisable\Http\Requests\CuztomisableRequest;

class AddressRequest extends CuztomisableRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        $this->merge([
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
        return [
            'address' => 'required|string|max:255',
            'address_two' => 'nullable|string|max:255',
            'address_three' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'state_or_province' => 'required|string|max:255',
            'zip_or_postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:255',
            'shipping' => 'nullable|in:0,1',
            'billing' => 'nullable|in:0,1',
        ];
    }

}
