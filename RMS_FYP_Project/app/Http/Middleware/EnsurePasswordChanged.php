<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        //ye bar bar change password , logout page show kry ga user ko jb tk wo 1 dfa apna temporary
        //password change na kr ly 
        if ($user && $user->must_change_password
            && ! $request->routeIs('password.change*', 'logout', 'filament.*.auth.logout')) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}
