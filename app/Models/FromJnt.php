<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FromJnt extends Model
{
    protected $table = 'from_jnts';

    protected $fillable = [
        'user_id',
        'waybill_number',
        'sender',
        'cod',
        'status',
        'item_name',
        'submission_time',
        'receiver',
        'receiver_cellphone',
        'signingtime',
        'remarks',
        'province',
        'city',
        'barangay',
        'total_shipping_cost',
        'rts_reason',
    ];

    protected $casts = [
        'submission_time'     => 'datetime',
        'signingtime'         => 'datetime',
        'total_shipping_cost' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
