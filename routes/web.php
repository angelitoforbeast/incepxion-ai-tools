<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Livewire\Actions\Logout;
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
Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified', 'approved'])
    ->name('dashboard');

// Profile is reachable while pending, so users can set up their API key while they wait
Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
