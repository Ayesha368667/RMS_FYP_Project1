<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        //Request handle jo request ai he login ki new,
        //Closure isko without authentication agy jany sy roky ga
        //string....roles 3no roles ki list btati he admin, doctor, patient
        $user = $request->user();
        //user ki id ko copy krna
        if(! $user || ! in_array($user->role, $roles, true)) {
            //!user mtlb ye user abhi exist or permitted hai ya nhi phir uska role check hoga
        abort(403);
        //error dy do usy wo access ni kr skta
    }
        return $next($request);
        //if no error.....give access and move to next
    }
}
