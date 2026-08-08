<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces a single active session per account (last-login-wins). When a user logs
 * in on another device, that device's session id becomes the account's current one;
 * any older device fails this check on its next request and is signed out.
 */
class EnsureSingleSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->current_session_id && $user->current_session_id !== $request->session()->getId()) {
            // Audit: record the device-conflict sign-out before logging out.
            \App\Models\AccessLog::create([
                'user_id'    => $user->id,
                'type'       => 'device_signout',
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 512),
                'location'   => \App\Services\GeoIp::locate($request->ip()),
            ]);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // AJAX/heartbeat (e.g. the video page ping) — return a signal the JS can act on.
            if ($request->expectsJson()) {
                return response()->json(['message' => 'signed_out'], 409);
            }

            return redirect()->route('login')->with('deviceSignout', true);
        }

        return $next($request);
    }
}
