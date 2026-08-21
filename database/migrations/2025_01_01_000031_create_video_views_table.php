<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Server-side record of who loaded which course video, when, and from where.
 *
 * A video cannot play without an OTP minted here, so a viewer cannot opt out of being
 * recorded — unlike the watermark, which lives in their browser and can be tampered with.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('course_id')->nullable();
            $table->unsignedBigInteger('lesson_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            // The code stamped into this playback's watermark, so a leaked frame ties
            // straight back to a row here.
            $table->string('watermark_code', 16)->nullable();
            $table->timestamp('created_at')->nullable();

            // Investigations run "who watched this lesson lately" and "where has this
            // account been watching from".
            $table->index(['lesson_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_views');
    }
};
