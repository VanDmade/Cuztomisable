<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A direct user->permission grant (bypassing roles) can apply within one specific organization
// only - a user might have "manage-users" granted in Org A but not in Org B, even though
// Permission itself stays a single app-wide definition with no organization of its own.
// Nullable - a grant with no organization applies globally, same "null = global" convention as
// roles.organization_id.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_permissions', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('permission_id')->constrained('organizations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('user_permissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
        });
    }
};
