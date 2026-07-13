<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rts_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('original_name');
            $table->string('disk')->default('local');
            $table->string('path');

            // queued → scanning → (needs_confirmation) → processing → done | failed | canceled
            $table->string('status')->default('queued');
            $table->dateTime('batch_at')->nullable();

            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('inserted')->default(0);
            $table->unsignedInteger('updated')->default(0);
            $table->unsignedInteger('skipped')->default(0);
            $table->unsignedInteger('error_rows')->default(0);

            // Invalid-transition guard: how many "regressive" rows were found + a small sample.
            $table->unsignedInteger('conflict_count')->default(0);
            $table->json('conflicts')->nullable();

            $table->text('error_message')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rts_uploads');
    }
};
