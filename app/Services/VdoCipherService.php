<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Talks to the VdoCipher API to mint a per-view playback OTP, with a moving
 * (forensic) watermark carrying the viewer's identity.
 *
 * Docs: https://www.vdocipher.com/docs/
 */
class VdoCipherService
{
    private const OTP_URL = 'https://dev.vdocipher.com/api/videos/%s/otp';

    public function configured(): bool
    {
        return filled(config('services.vdocipher.secret'));
    }

    /**
     * Get a single-use OTP + playbackInfo for a video, stamped with a moving watermark.
     *
     * @return array{otp: string, playbackInfo: string}
     */
    public function otp(string $videoId, string $watermarkText, int $ttl = 300): array
    {
        $secret = config('services.vdocipher.secret');
        if (blank($secret)) {
            throw new RuntimeException('VdoCipher is not configured yet. Add VDOCIPHER_API_SECRET to the server .env.');
        }

        $annotate = json_encode($this->buildAnnotation($watermarkText));

        $response = Http::withHeaders([
            'Authorization' => 'Apisecret '.$secret,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ])->timeout(20)->post(sprintf(self::OTP_URL, $videoId), [
            'ttl'      => $ttl,
            'annotate' => $annotate,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('VdoCipher OTP request failed ('.$response->status().'): '.$response->body());
        }

        $data = $response->json();
        if (empty($data['otp']) || empty($data['playbackInfo'])) {
            throw new RuntimeException('VdoCipher returned an unexpected response.');
        }

        return ['otp' => $data['otp'], 'playbackInfo' => $data['playbackInfo']];
    }

    /** Admin-editable watermark defaults (global). */
    public static function watermarkDefaults(): array
    {
        return [
            'color'    => 'FF3333',   // hex, no #
            'size'     => 12,
            'opacity'  => 50,         // percent (0–100)
            'speed'    => 6000,       // reposition interval in ms
            'two_tone' => true,       // add the fixed outlined mark
            'position' => 'top-left', // corner for the fixed mark
        ];
    }

    /** Current watermark settings (saved config merged over defaults). */
    public function watermark(): array
    {
        $saved = \App\Models\Setting::get('watermark', []);

        return array_merge(self::watermarkDefaults(), is_array($saved) ? $saved : []);
    }

    /**
     * Build the VdoCipher annotation array from the saved watermark settings:
     *  (1) a moving/random mark (anti-crop, forensic), plus
     *  (2) an optional fixed TWO-TONE mark (dark lining behind the colored text)
     *      that stays readable over light and dark footage.
     */
    private function buildAnnotation(string $text): array
    {
        $wm = $this->watermark();

        $color = '0x'.ltrim(strtoupper((string) $wm['color']), '#');
        $size  = (string) (int) $wm['size'];
        $alpha = number_format(max(0, min(100, (int) $wm['opacity'])) / 100, 2);

        $marks = [[
            'type'     => 'rtext',
            'text'     => $text,
            'alpha'    => $alpha,
            'color'    => $color,
            'size'     => $size,
            'interval' => (string) (int) $wm['speed'],
        ]];

        if (! empty($wm['two_tone'])) {
            $corners = [
                'top-left'     => [40, 40],
                'top-right'    => [900, 40],
                'bottom-left'  => [40, 620],
                'bottom-right' => [900, 620],
            ];
            [$x, $y] = $corners[$wm['position']] ?? $corners['top-left'];

            $marks[] = ['type' => 'text', 'text' => $text, 'alpha' => '0.60', 'color' => '0x000000', 'size' => $size, 'x' => $x + 2, 'y' => $y + 2];
            $marks[] = ['type' => 'text', 'text' => $text, 'alpha' => '0.95', 'color' => $color, 'size' => $size, 'x' => $x, 'y' => $y];
        }

        return $marks;
    }
}
