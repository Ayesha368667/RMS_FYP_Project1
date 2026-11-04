<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use Illiminate\Support\Facades\Auth;

class EnsureAccountIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$guards): Response
    {
        $user = $request->user();
       
        //Auto logout. Jab Admin kisi logged-in user ka is_active status false karta hai, 
        //Middleware next request par automatically us user ko logout kar deta hai 
        // aur login page par bhej deta hai.
        if ($user && ! $user->is_active) {
            //user database me exist krta he but abhi deactivate kia hua he uska account so logout him.
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect('/login')->withErrors([
                'email' => 'Your account is deactivated. Contact the admin.',
        ]);
    }

        return $next($request);
    }
}
