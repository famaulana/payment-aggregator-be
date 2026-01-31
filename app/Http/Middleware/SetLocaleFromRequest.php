<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->header('Accept-Language');

        if ($locale) {
            $locale = strtolower(str_replace('_', '-', $locale));
            $locale = substr($locale, 0, 2);

            $supportedLocales = ['en', 'id'];
            if (in_array($locale, $supportedLocales)) {
                app()->setLocale($locale);
            } else {
                app()->setLocale(config('app.fallback_locale', 'en'));
            }
        }

        return $next($request);
    }
}
