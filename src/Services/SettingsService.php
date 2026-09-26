<?php

namespace VanDmade\Cuztomisable\Services;

use VanDmade\Cuztomisable\Models\Setting;

/**
 * Reads/writes admin-editable settings and exposes public app config.
 */
class SettingsService
{

    public function get(string $key): array
    {
        $setting = Setting::where('key', $key)->first();
        return ['key' => $key, 'value' => $setting?->value, 'updated_at' => $setting?->updated_at];
    }

    // Not paginated/sortable via TableService like most admin lists - the rows here come from
    // config('cuztomisable.settings'), not the settings table (a registered key with no saved
    // value yet still needs to show up so an admin can set it for the first time).
    public function list($user): array
    {
        return array_map(fn($key) => [
            ...$this->get($key),
            'can_manage' => $this->canManage($user, $key),
        ], config('cuztomisable.settings', []));
    }

    public function permissionFor(string $key): string
    {
        return 'settings-'.str_replace('_', '-', $key);
    }

    public function canManage($user, string $key): bool
    {
        return $user->hasPermission('manage-settings') || $user->hasPermission($this->permissionFor($key));
    }

    public function save(string $key, string $value): array
    {
        $setting = Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        return ['key' => $key, 'value' => $setting->value, 'updated_at' => $setting->updated_at];
    }

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
            'administrator' => [
                'temporary_password' => [
                    'resend_after' => config('cuztomisable.account.administrator.temporary_password.resend_after', 300),
                ],
            ],
            'registration' => [
                'disabled' => config('cuztomisable.account.registration.disabled', false),
                'disable_message' => __('cuztomisable/authentication.registration.disabled', ['email' => config('cuztomisable.admin_email')]),
                'address' => config('cuztomisable.account.address', false),
            ],
            'navigation' => config('cuztomisable.app.navigation', []),
            // Only what the button needs to render - client_id/secret never leave the server.
            'social' => [
                'enabled' => config('cuztomisable.social.enabled', false),
                'providers' => collect(config('cuztomisable.social.providers', []))
                    ->map(fn ($provider, $key) => [
                        'enabled' => (bool) ($provider['enabled'] ?? false),
                        'logo' => (bool) ($provider['logo'] ?? true),
                        'label' => $provider['label'] ?? ucfirst($key),
                    ])
                    ->all(),
            ],
        ];
    }

}
