<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use VanDmade\Cuztomisable\Enums\Organizations\Role;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_user', function (Blueprint $table) {
            $table->enum('role', array_column(Role::cases(), 'value'))->default(Role::Member->value)->after('organization_id');
        });
    }

    public function down(): void
    {
        Schema::table('organization_user', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
