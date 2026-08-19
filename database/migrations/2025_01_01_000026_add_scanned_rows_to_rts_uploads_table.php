<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rts_uploads', function (Blueprint $table) {
            // Live "rows read so far" counter, updated during the parse for progress UI.
            $table->unsignedInteger('scanned_rows')->default(0)->after('total_rows');
        });
    }

    public function down(): void
    {
        Schema::table('rts_uploads', function (Blueprint $table) {
            $table->dropColumn('scanned_rows');
        });
    }
};
