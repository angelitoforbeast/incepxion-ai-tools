<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rts_uploads', function (Blueprint $table) {
            // Clean, user-facing message (no SQL/internals). error_message keeps the full
            // technical detail for admins.
            $table->text('user_message')->nullable()->after('error_message');
        });
    }

    public function down(): void
    {
        Schema::table('rts_uploads', function (Blueprint $table) {
            $table->dropColumn('user_message');
        });
    }
};
