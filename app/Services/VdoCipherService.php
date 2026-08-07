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
        $annotate = json_encode([[
            'type'     => 'rtext',
            'text'     => $watermarkText,
            'alpha'    => '0.60',
            'color'    => '0xFF3333',
            'size'     => '15',
            'interval' => '4000',
        ]]);

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
