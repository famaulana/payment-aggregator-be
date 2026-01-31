<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->header('Accept-Language');

        if ($locale) {
            // Parse Accept-Language header (e.g., "id-ID, id;q=0.9, en;q=0.8")
            $locale = strtolower(str_replace('_', '-', $locale));
            $locale = substr($locale, 0, 2); // Extract first 2 characters: 'id' or 'en'

            // Validate that the locale is supported
            $supportedLocales = ['en', 'id'];
            if (in_array($locale, $supportedLocales)) {
                app()->setLocale($locale);
            } else {
                // Fallback to default locale if not supported
                app()->setLocale(config('app.fallback_locale', 'en'));
            }
        }

        return $next($request);
    }
}
