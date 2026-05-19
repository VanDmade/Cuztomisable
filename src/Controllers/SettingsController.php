<?php

namespace VanDmade\Cuztomisable\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class SettingsController extends Controller
{

    /* DO NOT MODIFY THIS FILE */

    public function all(): JsonResponse
    {
        try {
            // All settings used within the frontend will need to be returned here
            return $this->success([
                'home' => config('cuztomisable.app.home', '/portal'),
                'note_to_nosey' => __('cuztomisable/global.note_to_nosey'),
                'unauthorized_note' =>__('cuztomisable/global.unauthorized'),
                'login_with' => config()->get('cuztomisable.login.login_with', []),
                'remember' => config()->get('cuztomisable.login.remember', false),
                'multi_factor_authentication' => [
                    'enabled' => config()->get('cuztomisable.login.multi_factor_authentication.allowed', true),
                    'resend_after' => config()->get('cuztomisable.login.multi_factor_authentication.resend_after', 300),
                    'send_via' => config()->get('cuztomisable.login.multi_factor_authentication.send_via', ['email' => true]),
                    'expires_in' => config()->get('cuztomisable.account.code.expires_in', 300),
                ],
                'session_length' => config()->get('cuztomisable.login.session_length', 300),
                'verification' => config()->get('cuztomisable.login.verification', []),
                'passwords' => [
                    'reset_with' => config()->get('cuztomisable.account.passwords.reset_with', ['email' => true]),
                    'time_between_allowed_resets' => config()->get('cuztomisable.account.passwords.time_between_allowed_resets', 900),
                    'resend_after' => config()->get('cuztomisable.account.passwords.resend_after', 300),
                    'send_via' => config()->get('cuztomisable.account.passwords.send_via', ['email' => true]),
                    'requirements' => config()->get('cuztomisable.account.passwords.requirements', []),
                ],
                'locations' => [
                    'default_country' => config()->get('cuztomisable.locations.default_country', null),
                    'countries' => config()->get('cuztomisable.locations.countries', []),
                    'default_country_code' => config()->get('cuztomisable.locations.default_country_code', null),
                    'country_codes' => config()->get('cuztomisable.locations.country_codes', []),
                ],
                'registration' => [
                    'disabled' => config()->get('cuztomisable.account.registration.disabled', false),
                    'disable_message' => __('cuztomisable/authentication.registration.disabled', ['email' => env('CUZTOMISABLE_ADMIN')]),
                    'address' => config()->get('cuztomisable.account.address', false),
                ],
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

}