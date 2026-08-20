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

// Block until no RTS upload is being scanned/processed, so a deploy doesn't restart the
// worker out from under a running import. Exits 0 when idle, 1 if it gives up waiting
// (deploy.sh then asks before continuing).
Artisan::command('rts:wait-idle {--timeout=1500 : Seconds to wait before giving up}', function () {
    $limit   = (int) $this->option('timeout');
    $waited  = 0;
    $active  = fn () => \App\Models\RtsUpload::whereIn('status', ['queued', 'scanning', 'processing'])->count();

    if ($active() === 0) {
        $this->info('RTS worker is idle.');

        return 0;
    }

    $this->warn('An RTS import is still running — waiting for it to finish before restarting the worker.');

    while ($waited < $limit) {
        sleep(10);
        $waited += 10;

        if ($active() === 0) {
            $this->info("RTS worker is idle (waited {$waited}s).");

            return 0;
        }

        if ($waited % 60 === 0) {
            $this->line("  still running… {$waited}s");
        }
    }

    $this->error("Still running after {$limit}s — giving up waiting.");

    return 1;
})->purpose('Wait until no RTS upload is being processed');
