<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfitCalculation extends Model
{
    protected $fillable = [
        'user_id', 'cpp', 'cogs', 'shipping_fee', 'orders',
        'cod_price', 'cod_fee', 'rts', 'net_profit',
    ];

    protected $casts = [
        'cpp'          => 'decimal:2',
        'cogs'         => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'cod_price'    => 'decimal:2',
        'cod_fee'      => 'decimal:4',
        'rts'          => 'decimal:4',
        'net_profit'   => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
