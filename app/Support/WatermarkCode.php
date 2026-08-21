<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Short, opaque per-user code stamped into course-video watermarks.
 *
 * Real encryption is unusable here — the ciphertext runs to a hundred-odd characters and a
 * watermark has one readable line. This is a keyed one-way code instead: six characters that
 * mean nothing to a viewer, can't be forged without the secret, and are always the same for
 * the same account, so the same leaker can be recognised across videos.
 *
 * It isn't reversible by arithmetic. Codes are matched by generating them for each user and
 * comparing — fine at any user count this app will see.
 */
class WatermarkCode
{
    /** Crockford-style alphabet: no I, L, O or U, so a blurry screenshot stays readable. */
    private const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    private const LENGTH = 6;

    private const SECRET_KEY = 'watermark_code_secret';

    /**
     * The secret lives in settings, not APP_KEY. If APP_KEY were ever rotated (a server
     * move, a leaked key), every previously stamped video would stop being identifiable.
     */
    public static function secret(): string
    {
        $secret = Setting::get(self::SECRET_KEY);

        if (! is_string($secret) || $secret === '') {
            $secret = Str::random(64);
            Setting::put(self::SECRET_KEY, $secret);
        }

        return $secret;
    }

    /** The code for a user id, e.g. "K7M2QX". */
    public static function for(int $userId): string
    {
        $digest = hash_hmac('sha256', 'watermark:'.$userId, self::secret(), true);

        $code = '';
        for ($i = 0; $i < self::LENGTH; $i++) {
            $code .= self::ALPHABET[ord($digest[$i]) % strlen(self::ALPHABET)];
        }

        return $code;
    }

    /**
     * Which user does a code belong to? Accepts the code with or without its "WM-" prefix
     * and ignores case, since it is usually typed off a screenshot.
     */
    public static function resolve(string $code): ?User
    {
        $code = strtoupper(trim($code));
        $code = Str::of($code)->replaceFirst('WM-', '')->toString();

        if ($code === '') {
            return null;
        }

        foreach (User::query()->select('id')->cursor() as $user) {
            if (self::for($user->id) === $code) {
                return User::find($user->id);
            }
        }

        return null;
    }
}
