<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Livewire\Actions\Logout;
use App\Livewire\AdCopyGenerator;
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

// Pending-approval landing (logged in, but not yet approved)
Route::view('approval/pending', 'approval-pending')
    ->middleware('auth')
    ->name('approval.pending');

// Dashboard + tools require an approved account
Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('dashboard', function () {
        return view('dashboard', [
            'tools' => Tool::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    })->name('dashboard');

    Route::get('tools/ad-copy-generator', AdCopyGenerator::class)->name('tools.ad-copy');
    Route::get('tools/rts-processor', RtsProcessor::class)->name('tools.rts');
    Route::view('tools/profit-calculator', 'tools.profit-calculator')->name('tools.profit');
});

// Admin — separate routes for each section
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::redirect('/', '/admin/users');
    Route::get('users', UserManager::class)->name('admin.users');
    Route::get('prompts', PromptManager::class)->name('admin.prompts');
    Route::get('logs', GenerationLog::class)->name('admin.logs');
});

// Profile (view-only) is reachable while pending; Settings requires an approved account
Route::view('profile', 'profile')->middleware(['auth'])->name('profile');
Route::view('settings', 'settings')->middleware(['auth', 'approved'])->name('settings');

require __DIR__.'/auth.php';
