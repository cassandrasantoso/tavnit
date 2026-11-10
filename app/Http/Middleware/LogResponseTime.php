<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Times every API request. The time goes into storage/logs/timing-*.log
 * and into a Server-Timing header, which browser dev tools show
 * under Network → Timing.
 */
class LogResponseTime
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = hrtime(true);

        $response = $next($request);

        $ms = round((hrtime(true) - $start) / 1_000_000, 1);

        $response->headers->set('Server-Timing', "app;dur={$ms}");

        Log::channel('timing')->info('request', [
            'method' => $request->method(),
            // The route pattern, not the URL, so /refills/12 and /refills/13 group together.
            'route' => $request->route()?->uri() ?? $request->path(),
            'status' => $response->getStatusCode(),
            'ms' => $ms,
        ]);

        return $response;
    }
}
