<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('from_jnts', function (Blueprint $table) {
            $table->id();
            // Per-user isolation: each seller account only sees its own shipments.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('waybill_number');
            $table->string('sender')->nullable();
            $table->string('cod')->nullable();
            $table->string('status')->nullable();
            $table->string('item_name')->nullable();
            $table->dateTime('submission_time')->nullable();
            $table->string('receiver')->nullable();
            $table->string('receiver_cellphone')->nullable();
            $table->dateTime('signingtime')->nullable();
            $table->text('remarks')->nullable();
            $table->string('province')->nullable();
            $table->string('city')->nullable();
            $table->string('barangay')->nullable();
            $table->decimal('total_shipping_cost', 12, 2)->nullable();
            $table->string('rts_reason')->nullable();
            $table->timestamps();

            // One row per waybill per user (upsert key).
            $table->unique(['user_id', 'waybill_number']);
            // Dashboard filters by user + date range and groups by sender/item.
            $table->index(['user_id', 'submission_time']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('from_jnts');
    }
};
