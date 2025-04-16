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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->bigInteger('deleted_by')->unsigned()->nullable();
            $table->foreign('deleted_by')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('set null');
            $table->bigInteger('created_by')->unsigned()->nullable();
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('set null');
            $table->string('name', 64);
            $table->string('first_name', 64);
            $table->string('middle_name', 64)->nullable();
            $table->string('last_name', 64);
            $table->string('suffix', 8)->nullable();
            $table->string('title', 128)->nullable();
            $table->string('username', 128)->nullable();
            $table->string('email', 128)->unique();
            $table->datetime('email_verified_at')->nullable();
            $table->string('password', 64);
            $table->string('gender', 32)->nullable();
            $table->string('timezone', 64)->default('UTC');
            $table->boolean('locked')->default(false);
            $table->boolean('change_password')->default(false);
            $table->boolean('multi_factor_authentication')->default(false);
            $table->boolean('admin')->default(false);
            $table->integer('attempts')->default(0);
            $table->datetime('attempt_timer')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
