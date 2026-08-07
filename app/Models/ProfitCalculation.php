<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfitCalculation extends Model
{
    protected $fillable = [
        'user_id', 'type', 'cpp', 'cogs', 'shipping_fee', 'orders',
        'cod_price', 'cod_fee', 'rts', 'net_profit',
        'target_net_profit', 'suggested_rts', 'suggested_cpp',
    ];

    protected $casts = [
        'cpp'               => 'decimal:2',
        'cogs'              => 'decimal:2',
        'shipping_fee'      => 'decimal:2',
        'cod_price'         => 'decimal:2',
        'cod_fee'           => 'decimal:4',
        'rts'               => 'decimal:4',
        'net_profit'        => 'decimal:2',
        'target_net_profit' => 'decimal:2',
        'suggested_rts'     => 'decimal:4',
        'suggested_cpp'     => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
