<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AccessLog extends Model
{
    protected $fillable = ['user_id', 'type', 'ip_address', 'user_agent', 'location'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Friendly device label from a user-agent string, e.g. "Chrome on Windows". */
    public static function formatDevice(?string $ua): string
    {
        if (blank($ua)) {
            return '—';
        }

        $os = match (true) {
            Str::contains($ua, 'Windows')                  => 'Windows',
            Str::contains($ua, ['iPhone', 'iPad', 'iOS'])  => 'iOS',
            Str::contains($ua, 'Mac OS')                   => 'macOS',
            Str::contains($ua, 'Android')                  => 'Android',
            Str::contains($ua, 'Linux')                    => 'Linux',
            default                                        => 'Unknown OS',
        };

        $browser = match (true) {
            Str::contains($ua, 'Edg')                      => 'Edge',
            Str::contains($ua, ['OPR', 'Opera'])           => 'Opera',
            Str::contains($ua, 'Chrome')                   => 'Chrome',
            Str::contains($ua, 'Firefox')                  => 'Firefox',
            Str::contains($ua, 'Safari')                   => 'Safari',
            default                                        => 'Browser',
        };

        return "{$browser} on {$os}";
    }
}
