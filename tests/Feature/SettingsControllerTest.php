<?php

namespace VanDmade\Cuztomisable\Tests\Feature;

use VanDmade\Cuztomisable\Models\Setting;
use VanDmade\Cuztomisable\Tests\Concerns\ActsAsAdmin;
use VanDmade\Cuztomisable\Tests\TestCase;

/**
 * Settings: the public frontend payload, reading one registered setting, and the admin settings page - listing the
 * registered keys and saving one, allowed by manage-settings or the key's own settings-{key} permission.
 */
class SettingsControllerTest extends TestCase
{

    use ActsAsAdmin;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        // Two registered keys, so per-key permissions can be told apart
        $app['config']->set('cuztomisable.settings', ['cookie_message', 'support_email']);
    }

    public function test_all_returns_the_full_frontend_settings_payload(): void
    {
        // Public - no sign-in. The frontend reads these keys, so the shape is what's checked here.
        $this->getJson('/api/'.ltrim(config('cuztomisable.app.settings_url', 'cuztomisable/settings'), '/'))
            ->assertOk()
            ->assertJsonStructure([
                'home', 'login_with', 'remember', 'multi_factor_authentication' => ['enabled', 'expires_in'],
                'session_length', 'passwords' => ['requirements'], 'locations', 'registration' => ['disabled'],
                'navigation', 'social' => ['enabled', 'providers'],
            ]);
    }

    public function test_get_returns_a_registered_settings_value(): void
    {
        Setting::create(['key' => 'cookie_message', 'value' => 'We use cookies']);

        $this->getJson('/api/settings/cookie_message')
            ->assertOk()
            ->assertJsonPath('key', 'cookie_message')
            ->assertJsonPath('value', 'We use cookies');
    }

    public function test_get_on_a_registered_key_with_nothing_saved_yet_returns_null(): void
    {
        $this->getJson('/api/settings/support_email')->assertOk()->assertJsonPath('value', null);
    }

    public function test_get_on_a_key_that_isnt_registered_returns_not_found(): void
    {
        $this->getJson('/api/settings/database_password')->assertNotFound();
    }

    public function test_list_needs_a_signed_in_user(): void
    {
        $this->getJson('/api/settings')->assertUnauthorized();
    }

    public function test_list_shows_every_registered_key_and_which_ones_the_user_can_manage(): void
    {
        $this->actingAsUserWith('settings-cookie-message');
        Setting::create(['key' => 'cookie_message', 'value' => 'Hi']);

        $settings = collect($this->getJson('/api/settings')->assertOk()->json('settings'))->keyBy('key');

        $this->assertEqualsCanonicalizing(['cookie_message', 'support_email'], $settings->keys()->all());
        $this->assertSame('Hi', $settings['cookie_message']['value']);
        $this->assertTrue($settings['cookie_message']['can_manage']);
        $this->assertFalse($settings['support_email']['can_manage']);
    }

    public function test_manage_settings_can_manage_every_key(): void
    {
        $this->actingAsUserWith('manage-settings');

        $settings = collect($this->getJson('/api/settings')->assertOk()->json('settings'));
        $this->assertTrue($settings->every(fn($setting) => $setting['can_manage']));
    }

    public function test_save_with_manage_settings_stores_the_value(): void
    {
        $this->actingAsUserWith('manage-settings');

        $this->postJson('/api/settings', ['key' => 'support_email', 'value' => 'help@example.test'])
            ->assertOk()
            ->assertJsonPath('setting.value', 'help@example.test');

        $this->assertDatabaseHas('settings', ['key' => 'support_email', 'value' => 'help@example.test']);
    }

    public function test_save_overwrites_an_existing_value(): void
    {
        $this->actingAsUserWith('manage-settings');
        Setting::create(['key' => 'cookie_message', 'value' => 'Old']);

        $this->postJson('/api/settings', ['key' => 'cookie_message', 'value' => 'New'])->assertOk();

        $this->assertSame(1, Setting::where('key', 'cookie_message')->count());
        $this->assertSame('New', Setting::where('key', 'cookie_message')->value('value'));
    }

    public function test_save_with_only_that_keys_permission_is_allowed_for_that_key_alone(): void
    {
        $this->actingAsUserWith('settings-cookie-message');

        $this->postJson('/api/settings', ['key' => 'cookie_message', 'value' => 'Allowed'])->assertOk();
        $this->postJson('/api/settings', ['key' => 'support_email', 'value' => 'nope@example.test'])->assertForbidden();

        $this->assertDatabaseMissing('settings', ['key' => 'support_email']);
    }

    public function test_save_without_any_settings_permission_is_forbidden(): void
    {
        $this->actingAsUserWithout();

        $this->postJson('/api/settings', ['key' => 'cookie_message', 'value' => 'Sneaky'])->assertForbidden();
        $this->assertDatabaseMissing('settings', ['key' => 'cookie_message']);
    }

    public function test_save_on_a_key_that_isnt_registered_is_refused(): void
    {
        $this->actingAsUserWith('manage-settings');

        $this->postJson('/api/settings', ['key' => 'app_secret', 'value' => 'x'])->assertForbidden();
        $this->assertDatabaseMissing('settings', ['key' => 'app_secret']);
    }

    public function test_save_requires_a_value(): void
    {
        $this->actingAsUserWith('manage-settings');

        $this->postJson('/api/settings', ['key' => 'cookie_message', 'value' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['value']);
    }

}
