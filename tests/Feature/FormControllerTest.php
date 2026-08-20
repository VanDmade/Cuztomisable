<?php

namespace VanDmade\Cuztomisable\Tests\Feature;

use VanDmade\Cuztomisable\Tests\TestCase;

class FormControllerTest extends TestCase
{

    // GET/POST /api/form/{page} - behind auth:sanctum (Sanctum::actingAs()), and also has its own
    // explicit Auth::check() guard inside the controller.

    // get() finds the latest form row for the authenticated user where current = $page.
    public function test_get_returns_null_when_no_form_state_exists_for_that_page(): void
    {
    }

    public function test_get_returns_the_latest_saved_form_state_for_that_page(): void
    {
    }

    // save() is an updateOrCreate keyed on (user_id, current) - saving the same page twice updates
    // the same row rather than creating a second one. to_params/current_params/form get normalized
    // through normalizeJson() (arrays get json_encode'd, already-JSON strings pass through, empty/null
    // stays null) before storage, then decoded back out via the model's own Attribute casts on read.
    public function test_save_creates_a_new_form_state_row(): void
    {
    }

    public function test_save_on_the_same_page_updates_the_existing_row_instead_of_creating_a_second_one(): void
    {
    }

    public function test_save_accepts_params_as_either_an_array_or_an_already_encoded_json_string(): void
    {
    }

}
