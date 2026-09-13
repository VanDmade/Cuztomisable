<?php

namespace VanDmade\Cuztomisable\Database\Seeders;

use Illuminate\Database\Seeder;
use VanDmade\Cuztomisable\Models\Setting;

/**
 * Seeds default values for the admin-editable settings.
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        // firstOrCreate, not updateOrCreate - re-seeding must never clobber a value an admin has
        // already customized. This only ever fills in a starting default the first time.
        Setting::firstOrCreate(
            ['key' => 'cookie_message'],
            ['value' => 'We use cookies to keep you signed in and improve your experience. By continuing, you agree to our use of cookies. <a href="/privacy-policy" target="_blank" rel="noopener">Learn more</a>']
        );
    }
}
