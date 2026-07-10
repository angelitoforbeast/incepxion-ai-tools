<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name', 'email', 'password', 'avatar', 'status', 'role',
    'plan_id', 'approved_at', 'approved_by', 'last_login_at', 'email_verified_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** Default attribute values for new users. */
    protected $attributes = [
        'status' => 'pending',
        'role'   => 'user',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'approved_at'       => 'datetime',
            'last_login_at'     => 'datetime',
            'password'          => 'hashed',
        ];
    }

    /** Assign the Free plan to every new user by default. */
    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (is_null($user->plan_id)) {
                $user->plan_id = Plan::where('slug', 'free')->value('id');
            }
        });
    }

    // ---------- Relationships ----------

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(UserApiKey::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function generations(): HasMany
    {
        return $this->hasMany(Generation::class);
    }

    public function usageDaily(): HasMany
    {
        return $this->hasMany(UsageDaily::class);
    }

    // ---------- Quota ----------

    public function usageToday(): int
    {
        return (int) ($this->usageDaily()->where('date', now()->toDateString())->value('count') ?? 0);
    }

    public function dailyQuota(): int
    {
        return (int) ($this->plan?->daily_quota ?? 0);
    }

    public function remainingQuota(): int
    {
        return max(0, $this->dailyQuota() - $this->usageToday());
    }

    public function hasQuotaLeft(): bool
    {
        return $this->usageToday() < $this->dailyQuota();
    }

    /** Increment today's usage counter (atomic upsert). */
    public function recordUsage(): void
    {
        $row = UsageDaily::firstOrCreate(
            ['user_id' => $this->id, 'date' => now()->toDateString()],
            ['count' => 0],
        );
        $row->increment('count');
    }

    // ---------- Helpers ----------

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** Get the user's stored API key row for a provider. */
    public function apiKeyFor(string $provider = 'openai'): ?UserApiKey
    {
        return $this->apiKeys()->where('provider', $provider)->first();
    }
}
