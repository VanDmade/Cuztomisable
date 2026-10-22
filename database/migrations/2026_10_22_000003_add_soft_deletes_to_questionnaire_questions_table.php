<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Questions are soft-deleted from the admin area, so everyone's answers to them stay on record - a hard delete would
// cascade to questionnaire_answers.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questionnaire_questions', function (Blueprint $table) {
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('questionnaire_questions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('deleted_by');
            $table->dropSoftDeletes();
        });
    }
};
