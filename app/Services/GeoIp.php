<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Best-effort IP → location. Never throws and never blocks meaningfully:
 * short timeout, try/catch, cached per IP, and private/loopback IPs are skipped.
 */
class GeoIp
{
    public static function locate(?string $ip): ?string
    {
        if (blank($ip)) {
            return null;
        }

        if (in_array($ip, ['127.0.0.1', '::1'], true)
            || Str::startsWith($ip, ['10.', '192.168.', '172.16.', '172.17.', '172.18.', '172.19.', '172.2', '172.30.', '172.31.', 'fe80:', 'fc', 'fd'])) {
            return 'Local network';
        }

        $key = 'geoip:'.$ip;
        if ($cached = Cache::get($key)) {
            return $cached;
        }

        try {
            $res = Http::timeout(2)->get("https://ipwho.is/{$ip}");
            if ($res->ok() && $res->json('success') === true) {
                $location = collect([$res->json('city'), $res->json('region'), $res->json('country')])
                    ->filter()->implode(', ');
                if ($location !== '') {
                    Cache::put($key, $location, now()->addDays(7)); // cache successes only
                    return $location;
                }
            }
        } catch (\Throwable $e) {
            // network/geo failure — never break the caller
        }

        return null;
    }
}
