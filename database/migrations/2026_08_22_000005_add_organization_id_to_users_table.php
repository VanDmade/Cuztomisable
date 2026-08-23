<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// The single source of truth for a user's *current* organization - replaces the organization_user
// pivot's `current` boolean (dropped below), which couldn't guarantee only one row was ever
// current and required an extra join to resolve. Nullable - not every user belongs to one.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained('organizations')->nullOnDelete();
        });
        Schema::table('organization_user', function (Blueprint $table) {
            $table->dropColumn('current');
        });
    }

    public function down(): void
    {
        Schema::table('organization_user', function (Blueprint $table) {
            $table->boolean('current')->default(false);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
        });
    }
};
