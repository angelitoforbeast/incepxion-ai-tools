<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks approved-but-expired accounts from the tools/dashboard and sends them
 * to the "Settle your account" page. Admins are never blocked.
 */
class EnsureNotExpired
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isAdmin() && $user->isExpired()) {
            return redirect()->route('settle');
        }

        return $next($request);
    }
}
