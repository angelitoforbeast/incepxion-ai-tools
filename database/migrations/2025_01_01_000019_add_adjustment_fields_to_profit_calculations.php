<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profit_calculations', function (Blueprint $table) {
            $table->string('type')->default('net')->after('user_id'); // net | adjustment
            $table->decimal('target_net_profit', 14, 2)->nullable()->after('net_profit');
            $table->decimal('suggested_rts', 10, 4)->nullable()->after('target_net_profit');
            $table->decimal('suggested_cpp', 14, 2)->nullable()->after('suggested_rts');
        });
    }

    public function down(): void
    {
        Schema::table('profit_calculations', function (Blueprint $table) {
            $table->dropColumn(['type', 'target_net_profit', 'suggested_rts', 'suggested_cpp']);
        });
    }
};
