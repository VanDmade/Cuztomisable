<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Nullable - a role can be a global default (organization_id null, e.g. "Basic User" /
// "Administrator") usable across every organization, or created by/for one specific organization.
// Permission stays app-wide (no organization column) - the set of possible permissions is fixed
// by the app's own code, not something an organization defines; user_permissions carries the
// per-organization grant instead (see the migration adding organization_id there).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained('organizations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
        });
    }
};
