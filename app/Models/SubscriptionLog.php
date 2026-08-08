<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionLog extends Model
{
    protected $fillable = [
        'user_id', 'admin_id', 'action', 'months',
        'old_expires_at', 'new_expires_at', 'note',
    ];

    protected function casts(): array
    {
        return [
            'old_expires_at' => 'datetime',
            'new_expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /** Record a change to a user's access expiry. */
    public static function record(User $user, string $action, $oldExpiry, $newExpiry, ?int $months = null, ?string $note = null, ?int $adminId = null): void
    {
        static::create([
            'user_id'        => $user->id,
            'admin_id'       => $adminId ?? auth()->id(),
            'action'         => $action,
            'months'         => $months,
            'old_expires_at' => $oldExpiry,
            'new_expires_at' => $newExpiry,
            'note'           => $note,
        ]);
    }
}
