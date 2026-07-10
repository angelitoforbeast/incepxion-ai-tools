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
                ->withErrors(['email' => 'Hindi natuloy ang Google sign-in. Subukan ulit.']);
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

        return redirect()->intended(route('dashboard'));
    }
}
