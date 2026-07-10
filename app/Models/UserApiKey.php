<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class UserApiKey extends Model
{
    protected $fillable = [
        'user_id', 'provider', 'key_encrypted', 'key_last4',
        'label', 'is_valid', 'last_used_at',
    ];

    protected $casts = [
        'key_encrypted' => 'encrypted', // auto encrypt/decrypt at rest
        'is_valid'      => 'boolean',
        'last_used_at'  => 'datetime',
    ];

    /** Never expose the raw key when serializing. */
    protected $hidden = ['key_encrypted'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Store a plaintext key: encrypt it and record the last 4 chars for display. */
    public function setKey(string $plain): void
    {
        $this->key_encrypted = $plain;
        $this->key_last4 = Str::substr($plain, -4);
    }

    /** Decrypted plaintext key (used only server-side at generation time). */
    public function plainKey(): ?string
    {
        return $this->key_encrypted;
    }

    /** Masked form for UI, e.g. "sk-…4f2a". */
    public function masked(): string
    {
        return 'sk-…'.($this->key_last4 ?? '????');
    }
}
