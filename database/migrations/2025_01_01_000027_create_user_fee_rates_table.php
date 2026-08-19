<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_fee_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('effective_date');
            $table->decimal('cod_fee_rate', 8, 6);      // decimal, e.g. 0.020000 for 2%
            $table->decimal('cod_fee_vat_rate', 8, 6);  // decimal, e.g. 0.120000 for 12%
            $table->timestamps();

            $table->unique(['user_id', 'effective_date']);
            $table->index(['user_id', 'effective_date']);
        });

        // Migrate existing single-value rates → one "from the beginning" dated entry.
        foreach (DB::table('users')->whereNotNull('remit_fees')->get() as $u) {
            $fees = json_decode($u->remit_fees, true);
            if (isset($fees['cod_fee_rate'], $fees['cod_fee_vat_rate'])
                && $fees['cod_fee_rate'] !== null && $fees['cod_fee_vat_rate'] !== null) {
                DB::table('user_fee_rates')->insert([
                    'user_id'          => $u->id,
                    'effective_date'   => '2000-01-01',
                    'cod_fee_rate'     => $fees['cod_fee_rate'],
                    'cod_fee_vat_rate' => $fees['cod_fee_vat_rate'],
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_fee_rates');
    }
};
