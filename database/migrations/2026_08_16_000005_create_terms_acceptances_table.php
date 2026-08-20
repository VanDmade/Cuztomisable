<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Per-user acceptance record of a specific terms & conditions version, driving re-verification on change.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terms_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('terms_and_conditions_id')->constrained('terms_and_conditions')->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamp('accepted_at');
            $table->timestamps();
            $table->unique(['user_id', 'terms_and_conditions_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terms_acceptances');
    }
};
