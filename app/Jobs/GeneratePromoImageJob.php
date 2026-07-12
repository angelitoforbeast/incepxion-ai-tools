<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\AdCopyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GeneratePromoImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 150;
    public int $tries = 1;

    public function __construct(
        public int $userId,
        public string $prompt,
        public string $model,
        public int $size,
        public string $token,
    ) {}

    public function handle(AdCopyService $service): void
    {
        $key = "promo-image:{$this->token}";

        $user = User::find($this->userId);
        if (! $user) {
            Cache::put($key, ['status' => 'error', 'error' => 'User not found'], now()->addMinutes(15));

            return;
        }

        try {
            $bytes = $service->generateImage($user, $this->prompt, $this->model, $this->size);
            if ($bytes === '') {
                throw new \RuntimeException('No image returned.');
            }

            $name = 'promo-images/'.Str::uuid()->toString().'.png';
            Storage::disk('public')->put($name, $bytes);

            Cache::put($key, ['status' => 'done', 'url' => Storage::disk('public')->url($name)], now()->addMinutes(30));
        } catch (\Throwable $e) {
            Cache::put($key, ['status' => 'error', 'error' => $e->getMessage()], now()->addMinutes(15));
        }
    }

    public function failed(\Throwable $e): void
    {
        Cache::put("promo-image:{$this->token}", ['status' => 'error', 'error' => $e->getMessage()], now()->addMinutes(15));
    }
}
