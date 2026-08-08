<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    /** Send the user to Google's OAuth consent screen. */
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /** Handle the callback from Google. */
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Google sign-in failed. Please try again.']);
        }

        // 1. Existing social account → log in.
        $account = SocialAccount::where('provider', 'google')
            ->where('provider_id', $googleUser->getId())
            ->first();

        if ($account) {
            $user = $account->user;
        } else {
            // 2. Existing email → link. Otherwise 3. create a new user.
            $user = User::where('email', $googleUser->getEmail())->first();

            if (! $user) {
                $user = User::create([
                    'name'              => $googleUser->getName() ?: 'User',
                    'email'             => $googleUser->getEmail(),
                    'avatar'            => $googleUser->getAvatar(),
                    'password'          => null,
                    'email_verified_at' => now(),
                    // status defaults to 'pending'; plan auto-set to Free by the model.
                ]);
            }

            SocialAccount::create([
                'user_id'        => $user->id,
                'provider'       => 'google',
                'provider_id'    => $googleUser->getId(),
                'provider_email' => $googleUser->getEmail(),
                'avatar'         => $googleUser->getAvatar(),
            ]);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        Auth::login($user, remember: true);

        // Single active session (last-login-wins): record this session; any older
        // session on another device is signed out by EnsureSingleSession middleware.
        session()->regenerate();
        $user->forceFill(['current_session_id' => session()->getId()])->save();

        // Start the validity clock on first access for approved users without an expiry yet.
        if ($user->status === 'approved' && is_null($user->access_expires_at)) {
            $user->forceFill(['access_expires_at' => now()->addMonths(\App\Models\User::DEFAULT_VALIDITY_MONTHS)])->save();
        }

        // Access log (login event) — admin-only audit trail.
        \App\Models\AccessLog::create([
            'user_id'    => $user->id,
            'type'       => 'login',
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 512),
            'location'   => \App\Services\GeoIp::locate(request()->ip()),
        ]);

        return redirect()->intended(route('dashboard'));
    }
}
