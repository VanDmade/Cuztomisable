<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// The formora table's own model already sets user_id to null for a guest (Form::boot()), but
// the column itself was never actually made nullable - so any anonymous save (e.g. progress on
// a multi-step registration form before the user has an account) fails with a NOT NULL
// constraint violation instead of the silent, best-effort save it's meant to be.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('formora', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        Schema::table('formora', function (Blueprint $table) {
            $table->bigInteger('user_id')->unsigned()->nullable()->change();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('formora', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        Schema::table('formora', function (Blueprint $table) {
            $table->bigInteger('user_id')->unsigned()->nullable(false)->change();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }
};
