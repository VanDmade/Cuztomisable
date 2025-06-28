<?php

namespace VanDmade\Cuztomisable\Traits\Validators;

trait Phone
{

    public function preparePhoneForValidation(): void
    {
        $this->merge([
            'phone' => strval(cleanPhone($this->input('phone'))),
        ]);
    }

    public function phoneValidationRules($countryCode = null, $phoneInput = null): array
    {
        $list = config('cuztomisable.locations.country_codes') ?? [];
        $size = 10;
        foreach ($list as $row) {
            if ($row['value'] == ($countryCode ?? 1)) {
                $size = $row['required_length'] ?? 10;
                break;
            }
        }
        $requirePhone = !config('cuztomisable.login.login_with.email', false)
            || config('cuztomisable.login.login_with.phone', false)
            || !empty($phoneInput) ? 'required' : 'nullable';
        return [
            'phone' => $requirePhone . '|size:' . $size,
            'country_code' => $requirePhone,
        ];
    }

}