<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_codes', function (Blueprint $table) {
            $table->unsignedInteger('attempt_counter')->default(0)->after('sent_via');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_codes', function (Blueprint $table) {
            $table->dropColumn('attempt_counter');
        });
    }
};
