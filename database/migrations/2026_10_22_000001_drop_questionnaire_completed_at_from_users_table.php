<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// An early version of the questionnaire added this to the users table. The package keeps nothing there now - whether
// a user is done is worked out from their answers - so it comes back off wherever it was added.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'questionnaire_completed_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('questionnaire_completed_at');
            });
        }
    }

    public function down(): void
    {
        // Nothing to put back - the column is no longer used
    }
};
