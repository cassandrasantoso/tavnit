<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

// Shows each page in the language the signed-in person picked.
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale;

        // No choice yet (or an old one we no longer offer): use the default.
        // Not config('app.locale'), because setLocale() changes that too.
        if (! in_array($locale, config('tavnit.locales'), true)) {
            $locale = config('tavnit.default_locale');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
