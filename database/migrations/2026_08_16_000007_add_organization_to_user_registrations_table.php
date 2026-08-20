<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Which organization a registration/invite grants membership to, once it's completed.
// Nullable - a non-multi-tenant deployment just never sets it.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_registrations', function (Blueprint $table) {
            $table->bigInteger('organization_id')->unsigned()->nullable()->after('user_id');
            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->onUpdate('cascade')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('user_registrations', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropColumn('organization_id');
        });
    }
};
