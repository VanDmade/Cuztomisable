<?php

namespace VanDmade\Cuztomisable\Tests\Feature\Authentication;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use VanDmade\Cuztomisable\Models\Users\User;
use VanDmade\Cuztomisable\Tests\Concerns\ActsAsAdmin;
use VanDmade\Cuztomisable\Tests\TestCase;

/**
 * cuztomisable.login.admins_only_on_web: when on, only admins can sign in on the website; the mobile app (the
 * X-App-Platform: mobile header) is unaffected. Off by default, so apps that don't set it behave as before.
 */
class AdminsOnlyOnWebTest extends TestCase
{

    use ActsAsAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        // A successful sign-in issues a Sanctum token; Sanctum's table is published into apps rather than loaded
        // by the package, so the test database doesn't have it - Sanctum's own columns
        if (!Schema::hasTable('personal_access_tokens')) {
            Schema::create('personal_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->morphs('tokenable');
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }
    }

    // Login lowercases the email it's given, so the account's must be lowercase to match
    private function user(bool $admin = false): User
    {
        $user = $this->createUser(['email' => strtolower(Str::random(10)).'@example.test']);
        if ($admin) {
            $user->forceFill(['admin' => true])->save();
        }
        return $user;
    }

    private function login(string $email, bool $mobile = false)
    {
        return $this->postJson('/api/login', ['username' => $email, 'password' => 'password'],
            $mobile ? ['X-App-Platform' => 'mobile'] : []);
    }

    public function test_a_non_admin_is_turned_away_from_the_website_when_it_is_admins_only(): void
    {
        config(['cuztomisable.login.admins_only_on_web' => true]);
        $user = $this->user();

        $this->login($user->email)
            ->assertStatus(403)
            ->assertJsonPath('message', __('cuztomisable/authentication.login.errors.admins_only'));
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_an_admin_can_still_sign_in_on_the_website(): void
    {
        config(['cuztomisable.login.admins_only_on_web' => true]);
        $admin = $this->user(admin: true);

        $this->login($admin->email)->assertOk();
    }

    public function test_the_mobile_app_is_not_affected(): void
    {
        // The app's version check is a separate gate - off here so only this rule is being tested
        config(['cuztomisable.login.admins_only_on_web' => true, 'cuztomisable.app.mobile_agent.enabled' => false]);
        $user = $this->user();

        $this->login($user->email, mobile: true)->assertOk();
    }

    public function test_everyone_can_sign_in_on_the_website_when_it_is_off(): void
    {
        config(['cuztomisable.login.admins_only_on_web' => false]);
        $user = $this->user();

        $this->login($user->email)->assertOk();
    }

}
