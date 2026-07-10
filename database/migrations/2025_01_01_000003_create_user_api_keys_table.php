<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('provider', ['openai', 'anthropic'])->default('openai');
            $table->text('key_encrypted');        // Laravel encrypted cast — never stored in plaintext
            $table->string('key_last4', 8)->nullable(); // for masked display, e.g. "…4f2a"
            $table->string('label')->nullable();
            $table->boolean('is_valid')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'provider']); // one key per provider per user
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_api_keys');
    }
};
