<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Generation extends Model
{
    protected $fillable = [
        'user_id', 'tool_id', 'provider', 'model', 'input', 'output',
        'input_tokens', 'output_tokens', 'cost', 'status', 'error', 'duration_ms',
    ];

    protected $casts = [
        'input'  => 'array',
        'output' => 'array',
        'cost'   => 'decimal:6',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }
}
