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
        Schema::table('user_ip_addresses', function (Blueprint $table) {
            $table->string('label')->nullable()->after('ip_address');
            $table->string('geo_label')->nullable()->after('label');
            $table->decimal('latitude', 10, 7)->nullable()->after('geo_label');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_ip_addresses', function (Blueprint $table) {
            $table->dropColumn(['label', 'geo_label', 'latitude', 'longitude']);
        });
    }
};
