<?php

namespace VanDmade\Cuztomisable\Concerns\Validators;

/**
 * Shared phone-number validation logic for FormRequests (registration, invites, etc).
 */
trait Phone
{

    protected function preparePhoneForValidation(): void
    {
        $phone = strval(cleanPhone($this->input('phone')));
        $this->merge([
            'phone' => $phone,
            'country_code' => $this->countryCodeFor($phone),
        ]);
    }

    // The country sent with a phone number - or, when a number came without one (e.g. an app that only sends the
    // number), the app's default country, rather than a "required" error on a field the form may not even show
    protected function countryCodeFor(string $phone): ?string
    {
        $countryCode = trim((string) $this->input('country_code'));
        if ($countryCode !== '') {
            return $countryCode;
        }
        $default = config('cuztomisable.locations.default_country_code', 1);
        return $phone !== '' && $default !== null ? (string) $default : null;
    }

    protected function phoneValidationRules(mixed $countryCode, mixed $phone): array
    {
        $list = config('cuztomisable.locations.country_codes') ?? [];
        $size = 10;
        foreach ($list as $row) {
            if ($row['value'] == ($countryCode ?? 1)) {
                $size = $row['required_length'] ?? 10;
                break;
            }
        }
        $requirePhone = !config('cuztomisable.login.login_with.email', false) ||
            config('cuztomisable.login.login_with.phone', false) ||
            ($phone ?? '') != '' ? 'required' : 'nullable';
        return [
            'phone' => $requirePhone.'|size:'.$size,
            'country_code' => $requirePhone,
        ];
    }

}
