<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserFeeRate extends Model
{
    protected $fillable = ['user_id', 'effective_date', 'cod_fee_rate', 'cod_fee_vat_rate'];

    protected function casts(): array
    {
        return [
            'effective_date'   => 'date',
            'cod_fee_rate'     => 'decimal:6',
            'cod_fee_vat_rate' => 'decimal:6',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
