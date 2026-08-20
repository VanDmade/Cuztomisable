<?php

namespace VanDmade\Cuztomisable\Tests\Feature;

use Illuminate\Support\Facades\Schema;
use VanDmade\Cuztomisable\Tests\TestCase;

class ServiceProviderTest extends TestCase
{

    public function test_the_package_boots_and_merges_its_config(): void
    {
        $this->assertNotNull(config('cuztomisable.app'));
        $this->assertNotNull(config('cuztomisable.login'));
    }

    // config/mobile.php was never merged under the old ten-file setup (a live bug) - now that
    // "mobile" is just a section of the one consolidated config/cuztomisable.php, this resolves.
    public function test_the_previously_unmerged_mobile_config_now_resolves(): void
    {
        $this->assertFalse(config('cuztomisable.mobile.refresh.reset_token'));
        $this->assertSame(30, config('cuztomisable.mobile.refresh.expires_in'));
    }

    public function test_the_packages_own_migrations_actually_ran(): void
    {
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('roles'));
        $this->assertTrue(Schema::hasTable('permissions'));
        $this->assertTrue(Schema::hasTable('organizations'));
    }

}
