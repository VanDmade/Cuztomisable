<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// The sign-up questionnaire: the questions an app asks (text comes from language keys), every user's first answer to
// each, and when the user finished it. See docs/13-questionnaire.md.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questionnaire_questions', function (Blueprint $table) {
            $table->id();
            // Stable name the app and seeders refer to the question by
            $table->string('key')->unique();
            $table->unsignedInteger('order')->default(0);
            // boolean, select, multi_select, text, number or date
            $table->string('type', 32);
            // Language keys, translated when the questions are sent out
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('description')->nullable();
            $table->string('image')->nullable();
            // Fixed choices for select / multi_select: [{"value": ..., "label": "lang.key"}]
            $table->json('options')->nullable();
            // Or a named list of choices from config('cuztomisable.questionnaire.sources')
            $table->string('source')->nullable();
            // Extras like allow_other, other_label, placeholder, min, max
            $table->json('settings')->nullable();
            // Where the answer is also written, so it can change later while the first answer stays on record
            $table->string('target_table')->nullable();
            $table->string('target_column')->nullable();
            // The column on target_table that holds the user's id - 'id' for the users table itself
            $table->string('target_key')->default('user_id');
            $table->boolean('required')->default(true);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('questionnaire_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('questionnaire_question_id')->constrained('questionnaire_questions')->cascadeOnUpdate()->cascadeOnDelete();
            $table->json('value')->nullable();
            // They picked "I can't find it" instead of one of the choices
            $table->boolean('other')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'questionnaire_question_id'], 'questionnaire_answers_user_question_unique');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('questionnaire_completed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('questionnaire_completed_at');
        });
        Schema::dropIfExists('questionnaire_answers');
        Schema::dropIfExists('questionnaire_questions');
    }
};
