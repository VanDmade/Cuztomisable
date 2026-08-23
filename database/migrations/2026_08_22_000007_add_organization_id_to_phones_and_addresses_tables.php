<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A phone/address can belong directly to an organization instead of a user - user_id is already
// nullable on both tables, so an organization-owned row just leaves it null rather than needing
// a polymorphic column. Nullable here too, for a user-owned row.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phones', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('user_id')->constrained('organizations')->nullOnDelete();
        });
        Schema::table('addresses', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('user_id')->constrained('organizations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('phones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
        });
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
        });
    }
};
