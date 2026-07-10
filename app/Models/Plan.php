<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name', 'slug', 'price', 'currency', 'interval',
        'daily_quota', 'monthly_quota', 'features', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'price'      => 'decimal:2',
        'features'   => 'array',
        'is_active'  => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
