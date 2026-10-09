<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsStaff
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Logged-in app users who aren't staff get "403 Forbidden".
        if (! $request->user()?->is_admin) {
            abort(403);
        }

        return $next($request);
    }
}
