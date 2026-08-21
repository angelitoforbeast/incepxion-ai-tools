<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hiding a tool from the dashboard grid is not the same as switching it off: a hidden tool
 * still works for anyone who has its URL. is_active would have conflated the two, so this
 * gives the grid its own flag.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tools', function (Blueprint $table) {
            $table->boolean('show_on_dashboard')->default(true)->after('is_active');
        });

        // RTS Processor is reachable by URL but no longer advertised on the dashboard.
        DB::table('tools')->where('slug', 'rts-processor')->update(['show_on_dashboard' => false]);
    }

    public function down(): void
    {
        Schema::table('tools', function (Blueprint $table) {
            $table->dropColumn('show_on_dashboard');
        });
    }
};
