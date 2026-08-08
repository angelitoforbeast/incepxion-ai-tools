<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('access_expires_at')->nullable()->after('approved_by');
        });

        // Grace for existing approved users: 3 months from rollout so no one is instantly locked out.
        DB::table('users')
            ->where('status', 'approved')
            ->whereNull('access_expires_at')
            ->update(['access_expires_at' => now()->addMonths(3)]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('access_expires_at');
        });
    }
};
