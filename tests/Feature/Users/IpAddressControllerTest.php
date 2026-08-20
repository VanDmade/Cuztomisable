<?php

namespace VanDmade\Cuztomisable\Tests\Feature\Users;

use VanDmade\Cuztomisable\Tests\TestCase;

class IpAddressControllerTest extends TestCase
{

    // ipQuery() (private helper) scopes to the caller's own IPs unless Auth::user()->admin is true,
    // in which case it's every IP in the system - test both an admin and a non-admin acting user for
    // get()/forget()/toggleDelete()/save(), not just one. forget()/toggleDelete() additionally need
    // permission:clear-user-logins.

    public function test_get_returns_the_ip_address_record(): void
    {
    }

    public function test_get_as_a_non_admin_cannot_see_another_users_ip_address(): void
    {
    }

    // table() has a raw SQL "remember" computed column (DB::raw with an IF(...) MySQL-ism) - this
    // will need a real MySQL-compatible test DB, not SQLite, or the query needs adjusting when this
    // gets migrated to the new IpAddressService. Worth flagging when you get here, not silently working
    // around it. Admin + no {id} lists every IP system-wide; admin can also filter by user_id.
    public function test_table_lists_the_authenticated_users_own_ip_addresses(): void
    {
    }

    public function test_table_as_an_admin_can_filter_by_user_id(): void
    {
    }

    // forget() only works on an IP that's actually remembered (remember=true) - a 202 (not an error
    // status really, just "nothing to do") if it isn't.
    public function test_forget_clears_the_remember_flag_on_a_remembered_ip(): void
    {
    }

    public function test_forget_on_an_ip_that_isnt_remembered_returns_202(): void
    {
    }

    public function test_toggle_delete_soft_deletes_then_restores(): void
    {
    }

    // save() only updates the label - nothing else about an IP record is user-editable through this endpoint.
    public function test_save_updates_the_ip_addresses_label(): void
    {
    }

}
