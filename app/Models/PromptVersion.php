<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromptVersion extends Model
{
    protected $fillable = ['tool_id', 'system_prompt', 'model', 'created_by'];

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
