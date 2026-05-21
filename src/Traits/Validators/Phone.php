<?php

namespace VanDmade\Cuztomisable\Traits\Validators;

trait Phone
{

    public function preparePhoneForValidation(): void
    {
        $countryCode = $this->input('country_code');
        $normalizedCountryCode = is_null($countryCode)
            ? null
            : (int) ltrim((string) $countryCode, '+');
        $this->merge([
            'phone' => strval(cleanPhone($this->input('phone'))),
            'country_code' => $normalizedCountryCode,
        ]);
    }

    public function phoneValidationRules(?int $countryCode = null, ?string $phoneInput = null): array
    {
        $list = config('cuztomisable.locations.country_codes') ?? [];
        $size = null;
        $codes = [];
        $resolvedCountryCode = $countryCode ?? config('cuztomisable.locations.default_country_code', 1);
        foreach ($list as $row) {
            if (isset($row['value'])) {
                $codes[] = $row['value'];
            }
            if ($row['value'] == $resolvedCountryCode) {
                $size = $row['required_length'] ?? null;
            }
        }
        $requirePhone = (!config('cuztomisable.login.login_with.email', false)
            || config('cuztomisable.login.login_with.phone', false))
            ? 'required' : 'required_without:email';
        if (!empty($phoneInput)) {
            $requirePhone = 'required';
        }
        $phoneRules = ['nullable', $requirePhone];
        if (!is_null($size)) {
            $phoneRules[] = 'size:'.$size;
        } else {
            $phoneRules[] = 'min:6';
            $phoneRules[] = 'max:15';
        }
        $countryCodeRules = [
            'nullable',
            $requirePhone === 'required' ? 'required' : 'required_with:phone',
        ];
        if (!empty($codes)) {
            $countryCodeRules[] = 'in:'.implode(',', $codes);
        }
        return [
            'phone' => implode('|', $phoneRules),
            'country_code' => implode('|', $countryCodeRules),
        ];
    }

}