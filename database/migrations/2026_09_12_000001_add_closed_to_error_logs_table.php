<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Lets an admin close out a batch of identical errors at once instead of one at a time.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('error_logs', function (Blueprint $table) {
            $table->timestamp('closed_at')->nullable()->after('parameters');
            $table->bigInteger('closed_by')->unsigned()->nullable()->after('closed_at');
            $table->foreign('closed_by')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('set null');
            $table->string('closed_reason', 256)->nullable()->after('closed_by');
        });
    }

    public function down(): void
    {
        Schema::table('error_logs', function (Blueprint $table) {
            $table->dropForeign(['closed_by']);
            $table->dropColumn(['closed_at', 'closed_by', 'closed_reason']);
        });
    }
};
