<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RtsUpload extends Model
{
    protected $table = 'rts_uploads';

    protected $fillable = [
        'user_id',
        'original_name',
        'disk',
        'path',
        'status',
        'batch_at',
        'total_rows',
        'processed_rows',
        'inserted',
        'updated',
        'skipped',
        'error_rows',
        'conflict_count',
        'conflicts',
        'error_message',
        'started_at',
        'finished_at',
        'canceled_at',
    ];

    protected $casts = [
        'batch_at'    => 'datetime',
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
        'canceled_at' => 'datetime',
        'conflicts'   => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
