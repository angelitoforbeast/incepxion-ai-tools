<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApproved
{
    /**
     * Block users whose account has not been approved by an admin yet.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isApproved()) {
            if (in_array($user->status, ['rejected', 'suspended'], true)) {
                return redirect()->route('account.rejected');
            }

            return redirect()->route('approval.pending');
        }

        return $next($request);
    }
}
