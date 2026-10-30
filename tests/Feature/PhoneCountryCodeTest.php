<?php

namespace VanDmade\Cuztomisable\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use VanDmade\Cuztomisable\Jobs\SendText;
use VanDmade\Cuztomisable\Tests\Concerns\ActsAsAdmin;
use VanDmade\Cuztomisable\Tests\TestCase;

/**
 * A phone number sent without its country code (an app that only sends the number) gets the app's default country
 * (cuztomisable.locations.default_country_code) instead of a "country code is required" error - an error on a field
 * the form usually doesn't show, which left the user stuck on "Please fix the errors below". Covers sign-up and
 * saving a profile.
 */
class PhoneCountryCodeTest extends TestCase
{

    use ActsAsAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function register(array $overrides = [])
    {
        return $this->postJson('/api/register', array_merge([
            'name' => 'New Person',
            'email' => 'new.person@example.test',
            'password' => 'Sup3r$ecretPass!',
        ], $overrides));
    }

    private function savedPhone(string $email): ?object
    {
        return DB::table('phones')
            ->join('users', 'users.id', '=', 'phones.user_id')
            ->where('users.email', $email)
            ->select('phones.number', 'phones.country_code')
            ->first();
    }

    public function test_signing_up_with_a_phone_but_no_country_code_uses_the_default_country(): void
    {
        config(['cuztomisable.locations.default_country_code' => 1]);

        $this->register(['phone' => '(555) 555-0123'])->assertOk();

        $phone = $this->savedPhone('new.person@example.test');
        $this->assertNotNull($phone);
        $this->assertSame('5555550123', $phone->number);
        $this->assertSame('1', (string) $phone->country_code);
    }

    public function test_signing_up_with_a_country_code_keeps_it(): void
    {
        $this->register(['phone' => '2071234567', 'country_code' => '44'])->assertOk();

        $this->assertSame('44', (string) $this->savedPhone('new.person@example.test')->country_code);
    }

    public function test_signing_up_without_a_phone_still_works(): void
    {
        $this->register()->assertOk();

        $this->assertNull($this->savedPhone('new.person@example.test'));
    }

    public function test_a_phone_number_of_the_wrong_length_is_still_rejected_on_the_phone_field(): void
    {
        $this->register(['phone' => '12345'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone'])
            ->assertJsonMissingValidationErrors(['country_code']);
    }

    public function test_saving_a_profile_with_a_phone_but_no_country_code_uses_the_default_country(): void
    {
        config(['cuztomisable.locations.default_country_code' => 1]);
        $me = $this->actingAsUserWithout();
        // A new number gets a verification text - queued, not really sent here
        Bus::fake([SendText::class]);

        $this->postJson('/api/profile', ['name' => $me->name, 'email' => $me->email, 'phone' => '5555550188'])
            ->assertOk();
        Bus::assertDispatched(SendText::class);

        $phone = $this->savedPhone($me->email);
        $this->assertSame('5555550188', $phone->number);
        $this->assertSame('1', (string) $phone->country_code);
    }

}
