<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Prune Livewire temporary upload files (abandoned uploads that were never submitted).
// Submitted uploads are cleaned immediately in RtsProcessor::submitUpload(); this catches
// the rest. Runs only if the scheduler cron is active.
Artisan::command('rts:prune-tmp', function () {
    $dir = storage_path('app/private/livewire-tmp');
    if (! is_dir($dir)) {
        $dir = storage_path('app/livewire-tmp'); // fallback for older disk layout
    }
    if (! is_dir($dir)) {
        return;
    }

    $cutoff = now()->subDay()->getTimestamp();
    $removed = 0;
    foreach (glob($dir.'/*') as $file) {
        if (is_file($file) && @filemtime($file) < $cutoff) {
            if (@unlink($file)) {
                $removed++;
            }
        }
    }
    $this->info("Pruned {$removed} stale temp upload file(s).");
})->purpose('Delete Livewire temp upload files older than 24h');

Schedule::command('rts:prune-tmp')->hourly()->withoutOverlapping();
