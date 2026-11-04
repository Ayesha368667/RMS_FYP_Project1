<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        // Admin/doctor panels ko English hi rehne dein
        if (! $request->is('admin*', 'doctor*')) {
            $locale = session('locale', config('app.locale'));

            if (in_array($locale, ['en', 'ur'], true)) {
                app()->setLocale($locale);
            }
        }

        return $next($request);
    }
}