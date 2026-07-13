<?php

namespace App\Services;

/**
 * Classifies J&T statuses and detects "regressive" transitions — the signal that a
 * WRONG (older) file was uploaded, e.g. a shipment already DELIVERED showing up again
 * as IN TRANSIT.
 */
class RtsStatus
{
    /** 'delivered' | 'returned' | 'other' */
    public static function kind(?string $status): string
    {
        $s = strtolower(trim((string) $status));
        if ($s === '') {
            return 'other';
        }
        if (str_contains($s, 'delivered')) {
            return 'delivered';
        }
        if (str_contains($s, 'return') || str_contains($s, 'rts')) {
            return 'returned';
        }

        return 'other';
    }

    /** Final states are never downgraded (delivered / returned). */
    public static function isFinal(?string $status): bool
    {
        return in_array(self::kind($status), ['delivered', 'returned'], true);
    }

    /**
     * A regression = the existing row is already in a FINAL state, but the incoming
     * status would move it to a different (earlier or different-final) state.
     */
    public static function isRegression(?string $existing, ?string $incoming): bool
    {
        return self::isFinal($existing) && self::kind($existing) !== self::kind($incoming);
    }
}
