<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rts_uploads', function (Blueprint $table) {
            // Cancellation signal the running job polls for (separate from `status`,
            // which the job itself keeps writing while it runs).
            $table->timestamp('canceled_at')->nullable()->after('finished_at');
        });
    }

    public function down(): void
    {
        Schema::table('rts_uploads', function (Blueprint $table) {
            $table->dropColumn('canceled_at');
        });
    }
};
