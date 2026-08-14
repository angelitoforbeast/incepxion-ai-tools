<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Per-user J&T remittance rates:
            //   { cod_fee_rate, cod_fee_vat_rate, shipping_fee_per_order (optional, anomaly-only) }
            $table->json('remit_fees')->nullable()->after('profit_inputs');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('remit_fees');
        });
    }
};
