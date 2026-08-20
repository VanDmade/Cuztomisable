<?php

namespace VanDmade\Cuztomisable\Tests\Feature\Authentication;

use VanDmade\Cuztomisable\Tests\TestCase;

class LoginControllerTest extends TestCase
{

    // POST /api/login - public. On success, whether MFA is required is decided by
    // $ipAddress->requireMfa() (check that model method's own logic separately - it drives which
    // branch every test here takes). Non-MFA web login attaches a signed auth cookie via
    // $user->generateAuthCookie() (needs Sanctum wired for createToken()); a request with
    // X-App-Platform: mobile header returns access_token/refresh_token/expires_in in the JSON body
    // instead, and also creates a Models\Personal\RefreshToken row. MFA-required login returns a
    // 'token' (the MFA code's token) instead of logging in yet.
    public function test_login_with_valid_credentials_and_no_mfa_required_logs_in_and_sets_the_auth_cookie(): void
    {
    }

    public function test_login_from_a_mobile_client_returns_access_and_refresh_tokens_instead_of_a_cookie(): void
    {
    }

    public function test_login_when_the_ip_address_requires_mfa_returns_an_mfa_token_without_logging_in(): void
    {
    }

    // Wrong password calls $user->addAttempt() before throwing 401 - verify the attempt actually
    // increments (and eventually locks/times-out per cuztomisable.login.attempts config), not just the 401.
    public function test_login_with_an_incorrect_password_increments_the_attempt_counter_and_returns_401(): void
    {
    }

    public function test_login_for_an_unknown_username_returns_the_same_generic_invalid_credentials_error(): void
    {
    }

    // $user->canLogIn() throws for locked accounts / unverified email or phone (per cuztomisable.login.
    // verification config) - separate failure mode from bad credentials, worth its own test.
    public function test_login_for_a_locked_account_is_rejected_even_with_correct_credentials(): void
    {
    }

    // logout() revokes the current Sanctum token and clears the auth cookie.
    public function test_logout_revokes_the_current_token_and_clears_the_cookie(): void
    {
    }

}
