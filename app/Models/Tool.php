<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tool extends Model
{
    protected $fillable = [
        'slug', 'name', 'description', 'icon', 'category',
        'is_active', 'show_on_dashboard', 'required_plan_id', 'config', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_on_dashboard' => 'boolean',
        'config'    => 'array',
    ];

    public function requiredPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'required_plan_id');
    }

    public function generations(): HasMany
    {
        return $this->hasMany(Generation::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
