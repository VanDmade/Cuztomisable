<?php

namespace VanDmade\Cuztomisable\Tests\Feature\Services\Logs;

use VanDmade\Cuztomisable\Tests\TestCase;

class TextLogServiceTest extends TestCase
{

    // create() delegates the phone lookup to PhoneService::findByNumber() rather than querying
    // Phone directly - it matches on cleaned digits, since numbers can arrive in different
    // formats depending on the caller (a Phone model's own fields vs raw request data on
    // RegistrationService::invite()).
    public function test_create_resolves_user_id_via_phoneservice(): void
    {
    }

    public function test_create_leaves_user_id_null_when_no_phone_matches(): void
    {
    }

    public function test_create_does_not_overwrite_an_explicitly_passed_user_id(): void
    {
    }

    public function test_create_matches_numbers_in_different_formats_to_the_same_phone(): void
    {
    }

    public function test_get_returns_the_log_entry(): void
    {
    }

    public function test_get_throws_when_the_log_entry_does_not_exist(): void
    {
    }

    // Same recipient/creator name-joining as EmailLogService - see that test class for the
    // shared reasoning.
    public function test_table_returns_recipient_and_created_by_names(): void
    {
    }

    public function test_table_names_are_null_when_the_fks_are_null(): void
    {
    }

    public function test_table_search_matches_by_recipient_name(): void
    {
    }

    public function test_table_search_matches_by_created_by_name(): void
    {
    }

}
