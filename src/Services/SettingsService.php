<?php

namespace VanDmade\Cuztomisable\Services;

class SettingsService
{

    public function find(): array
    {
        return [
            'home' => config('cuztomisable.app.home', '/portal'),
            'note_to_nosey' => __('cuztomisable/global.note_to_nosey'),
            'unauthorized_note' => __('cuztomisable/global.unauthorized'),
            'login_with' => config('cuztomisable.login.login_with', []),
            'remember' => config('cuztomisable.login.remember', false),
            'multi_factor_authentication' => [
                'enabled' => config('cuztomisable.login.multi_factor_authentication.allowed', true),
                'resend_after' => config('cuztomisable.login.multi_factor_authentication.resend_after', 300),
                'send_via' => config('cuztomisable.login.multi_factor_authentication.send_via', ['email' => true]),
                'expires_in' => config('cuztomisable.account.code.expires_in', 300),
            ],
            'session_length' => config('cuztomisable.login.session_length', 300),
            'verification' => config('cuztomisable.login.verification', []),
            'passwords' => [
                'reset_with' => config('cuztomisable.account.passwords.reset_with', ['email' => true]),
                'time_between_allowed_resets' => config('cuztomisable.account.passwords.time_between_allowed_resets', 900),
                'resend_after' => config('cuztomisable.account.passwords.resend_after', 300),
                'send_via' => config('cuztomisable.account.passwords.send_via', ['email' => true]),
                'requirements' => config('cuztomisable.account.passwords.requirements', []),
            ],
            'locations' => [
                'default_country' => config('cuztomisable.locations.default_country', null),
                'countries' => config('cuztomisable.locations.countries', []),
                'default_country_code' => config('cuztomisable.locations.default_country_code', null),
                'country_codes' => config('cuztomisable.locations.country_codes', []),
            ],
            'registration' => [
                'disabled' => config('cuztomisable.account.registration.disabled', false),
                'disable_message' => __('cuztomisable/authentication.registration.disabled', ['email' => env('CUZTOMISABLE_ADMIN')]),
                'address' => config('cuztomisable.account.address', false),
            ],
            'navigation' => config('cuztomisable.app.navigation', []),
        ];
    }

}
