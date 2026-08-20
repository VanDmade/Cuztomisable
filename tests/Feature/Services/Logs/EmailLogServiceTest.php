<?php

namespace VanDmade\Cuztomisable\Tests\Feature\Services\Logs;

use VanDmade\Cuztomisable\Tests\TestCase;

class EmailLogServiceTest extends TestCase
{

    // create() only resolves user_id when it isn't already passed in - an explicit user_id
    // (e.g. a future caller that already knows the recipient) should never be overwritten by the
    // lookup.
    public function test_create_resolves_user_id_from_the_first_to_address(): void
    {
    }

    public function test_create_leaves_user_id_null_when_no_user_matches(): void
    {
    }

    public function test_create_does_not_overwrite_an_explicitly_passed_user_id(): void
    {
    }

    // "to" can arrive as a plain string, a plain array, or an Illuminate\Support\Collection
    // (that's what LogEmail actually passes) - all three need to resolve the same recipient.
    public function test_create_resolves_user_id_when_to_is_a_collection(): void
    {
    }

    public function test_create_resolves_user_id_when_to_is_a_plain_string(): void
    {
    }

    public function test_get_returns_the_log_entry(): void
    {
    }

    public function test_get_throws_when_the_log_entry_does_not_exist(): void
    {
    }

    // table() left-joins users twice (ru for user_id, cu for created_by) and exposes
    // recipient_name/created_by_name - both should be null (not missing/erroring) when either FK
    // is null, since most recipients won't be known users.
    public function test_table_returns_recipient_and_created_by_names(): void
    {
    }

    public function test_table_names_are_null_when_the_fks_are_null(): void
    {
    }

    // search_columns includes ru.name/cu.name specifically so an admin can search by a person's
    // name, not just by raw numeric id - the whole point of the linking.
    public function test_table_search_matches_by_recipient_name(): void
    {
    }

    public function test_table_search_matches_by_created_by_name(): void
    {
    }

}
