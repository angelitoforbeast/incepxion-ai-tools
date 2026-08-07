<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profit_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('cpp', 12, 2)->default(0);
            $table->decimal('cogs', 12, 2)->default(0);
            $table->decimal('shipping_fee', 12, 2)->default(0);
            $table->unsignedInteger('orders')->default(0);
            $table->decimal('cod_price', 12, 2)->default(0);
            $table->decimal('cod_fee', 8, 4)->default(0);
            $table->decimal('rts', 6, 4)->default(0);
            $table->decimal('net_profit', 14, 2)->default(0);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profit_calculations');
    }
};
