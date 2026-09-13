<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Lets a user manually override their auto-detected timezone without the periodic browser sync
// (see cuztomisable.js's syncTimezoneIfStale) silently overwriting it back.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('timezone_auto')->default(true)->after('timezone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('timezone_auto');
        });
    }
};
