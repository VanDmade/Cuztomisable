<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// created_by tracks who published a version. requires_reacceptance decides whether publishing
// this version forces every user to re-accept, or only users who have never accepted anything.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('terms_and_conditions', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->boolean('requires_reacceptance')->default(false)->after('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('terms_and_conditions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn('requires_reacceptance');
        });
    }
};
