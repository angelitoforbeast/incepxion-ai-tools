<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Separates the moment a video was loaded from the periodic "still open" pings that follow.
 * Without the pings, a session paused today and resumed tomorrow was only ever recorded at
 * yesterday's time and IP.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('video_views', function (Blueprint $table) {
            $table->string('kind', 12)->default('start')->after('lesson_id');
        });
    }

    public function down(): void
    {
        Schema::table('video_views', function (Blueprint $table) {
            $table->dropColumn('kind');
        });
    }
};
