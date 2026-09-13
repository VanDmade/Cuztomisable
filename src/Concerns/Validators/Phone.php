<?php

namespace VanDmade\Cuztomisable\Concerns\Validators;

/**
 * Shared phone-number validation logic for FormRequests (registration, invites, etc).
 */
trait Phone
{

    protected function preparePhoneForValidation(): void
    {
        $this->merge([
            'phone' => strval(cleanPhone($this->input('phone'))),
        ]);
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
