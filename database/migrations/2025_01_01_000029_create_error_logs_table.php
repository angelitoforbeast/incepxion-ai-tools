<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_logs', function (Blueprint $table) {
            $table->id();
            $table->string('level', 20)->default('error');
            $table->string('exception')->nullable();
            $table->text('message')->nullable();
            $table->string('file')->nullable();
            $table->integer('line')->nullable();
            $table->longText('trace')->nullable();
            $table->string('url')->nullable();
            $table->string('method', 10)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_logs');
    }
};
