<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tracks who published a version, distinct from created_by (who uploaded/drafted it) - the two
// can be different admins/different times since publishing is a separate action from uploading.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('terms_and_conditions', function (Blueprint $table) {
            $table->foreignId('published_by')->nullable()->after('published_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('terms_and_conditions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('published_by');
        });
    }
};
