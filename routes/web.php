<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Livewire\Actions\Logout;
use App\Livewire\AdCopyGenerator;
use App\Livewire\Admin\CourseManager;
use App\Livewire\Admin\AccessLog;
use App\Livewire\Admin\ProfitHistory;
use App\Livewire\Courses\CourseIndex;
use App\Livewire\Courses\CourseShow;
use App\Livewire\ProfitCalculator;
use App\Livewire\RtsMonitor;
use App\Livewire\RtsProcessor;
use App\Livewire\Admin\GenerationLog;
use App\Livewire\Admin\PromptManager;
use App\Livewire\Admin\UserManager;
use App\Models\Tool;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

// Named logout (Breeze Livewire logs out via a component action; this adds a POST route for forms)
Route::post('logout', function (Logout $logout) {
    $logout();

    return redirect('/');
})->middleware('auth')->name('logout');

// Google OAuth
Route::get('auth/google', [GoogleController::class, 'redirect'])->name('google.redirect');
Route::get('auth/google/callback', [GoogleController::class, 'callback'])->name('google.callback');

// Pending-approval landing (logged in, but not yet approved). Polls and auto-advances
// to the dashboard the moment an admin approves the account.
Route::get('approval/pending', \App\Livewire\ApprovalPending::class)
    ->middleware('auth')
    ->name('approval.pending');

// Rejected / suspended landing — dedicated view (shows the rejection reason).
Route::get('account/rejected', \App\Livewire\AccountRejected::class)
    ->middleware('auth')
    ->name('account.rejected');

// Dashboard + tools require an approved AND non-expired account
Route::middleware(['auth', 'verified', 'approved', 'not-expired'])->group(function () {
    Route::get('dashboard', function () {
        return view('dashboard', [
            // Courses is a top-level sidebar item, not a grid tool.
            'tools' => Tool::where('is_active', true)->where('slug', '!=', 'courses')->orderBy('sort_order')->get(),
        ]);
    })->name('dashboard');

    Route::get('tools/ad-copy-generator', AdCopyGenerator::class)->name('tools.ad-copy');
    Route::get('tools/rts-processor', RtsProcessor::class)->name('tools.rts');

    // Plain (non-Livewire) cancel — works even if the page's Livewire runtime is stale.
    Route::post('tools/rts-processor/cancel', function (\Illuminate\Http\Request $request) {
        $upload = \App\Models\RtsUpload::where('user_id', auth()->id())
            ->whereKey($request->integer('upload'))
            ->whereIn('status', ['needs_confirmation', 'queued', 'scanning', 'processing'])
            ->first();

        if ($upload) {
            try {
                if ($upload->path && \Illuminate\Support\Facades\Storage::disk($upload->disk ?: 'local')->exists($upload->path)) {
                    \Illuminate\Support\Facades\Storage::disk($upload->disk ?: 'local')->delete($upload->path);
                }
            } catch (\Throwable $e) {
                // ignore
            }
            $upload->update(['canceled_at' => now(), 'status' => 'canceled', 'finished_at' => now()]);
        }

        return redirect()->route('tools.rts');
    })->name('tools.rts.cancel');
    Route::get('tools/rts-processor/monitoring', RtsMonitor::class)->name('tools.rts.monitor');
    Route::get('tools/rts-processor/remittance', \App\Livewire\RtsRemittance::class)->name('tools.rts.remittance');
    Route::get('tools/courses', CourseIndex::class)->name('tools.courses');
    Route::get('tools/courses/{course:slug}', CourseShow::class)->name('tools.courses.show');
    Route::get('tools/profit-calculator', ProfitCalculator::class)->name('tools.profit');
});

// Admin — the URL prefix is obfuscated so the panel is hard to find by guessing.
// (The real access gate is the 'admin' middleware; this just avoids drive-by probing.)
// Route NAMES stay 'admin.*', so the sidebar/nav links keep working automatically —
// change ADMIN_PREFIX below anytime to rotate the URL.
Route::middleware(['auth', 'admin'])->prefix('console-7k29fx')->group(function () {
    Route::get('/', fn () => redirect()->route('admin.users'));
    Route::get('users', UserManager::class)->name('admin.users');
    Route::get('prompts', PromptManager::class)->name('admin.prompts');
    Route::get('courses', CourseManager::class)->name('admin.courses');
    Route::get('profit-history', ProfitHistory::class)->name('admin.profit');
    Route::get('access-log', AccessLog::class)->name('admin.access');
    Route::get('subscriptions', \App\Livewire\Admin\SubscriptionManager::class)->name('admin.subscriptions');
    Route::get('subscriptions/log', \App\Livewire\Admin\SubscriptionLogs::class)->name('admin.subscriptions.log');
    Route::get('billing', \App\Livewire\Admin\BillingSettings::class)->name('admin.billing');
    Route::get('rts-data', \App\Livewire\Admin\RtsData::class)->name('admin.rts');
    Route::get('storage', \App\Livewire\Admin\Storage::class)->name('admin.storage');
    Route::get('logs', GenerationLog::class)->name('admin.logs');
});

// Settle / renew page — reachable by approved users even when expired (no not-expired guard).
Route::get('settle', \App\Livewire\Settle::class)->middleware(['auth', 'approved'])->name('settle');

// Lightweight session heartbeat (used by the course player to stop playback if the
// account is opened on another device). EnsureSingleSession returns 409 when stale.
Route::get('session/ping', fn () => response()->json(['ok' => true]))
    ->middleware('auth')->name('session.ping');

// Profile (view-only) is reachable while pending; Settings requires an approved account
Route::view('profile', 'profile')->middleware(['auth'])->name('profile');
Route::view('settings', 'settings')->middleware(['auth', 'approved'])->name('settings');

require __DIR__.'/auth.php';
