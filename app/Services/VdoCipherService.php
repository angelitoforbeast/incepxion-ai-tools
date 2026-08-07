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

        // Moving text watermark ("rtext" = repositions every interval ms), so it
        // can't simply be cropped out, and a leaked screen-recording is traceable.
        // Hybrid watermark:
        // (1) a moving/random white mark for anti-crop forensic security, plus
        // (2) a fixed TWO-TONE mark (black "lining" behind white) near the bottom-left,
        //     which stays readable over both light and dark footage.
        $annotate = json_encode([
            [
                'type'     => 'rtext',
                'text'     => $watermarkText,
                'alpha'    => '0.45',
                'color'    => '0xFF3333',
                'size'     => '12',
                'interval' => '6000',
            ],
            // black outline/shadow (offset behind) — x/y are integer pixels
            [
                'type'  => 'text',
                'text'  => $watermarkText,
                'alpha' => '0.55',
                'color' => '0x000000',
                'size'  => '12',
                'x'     => 42,
                'y'     => 42,
            ],
            // red text on top (black lining behind keeps it readable)
            [
                'type'  => 'text',
                'text'  => $watermarkText,
                'alpha' => '0.90',
                'color' => '0xFF3333',
                'size'  => '12',
                'x'     => 40,
                'y'     => 40,
            ],
        ]);

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
}
