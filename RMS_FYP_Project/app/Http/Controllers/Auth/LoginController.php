<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

//Iska matlab hai ke ye controller app/Http/Controllers/Auth folder ke andar hai.
//Laravel ke main Controller class ko import karta hai, taake hum uski functionality use kar saken.
//Request user ki taraf se aane wala data receive karta hai, jaise email, password aur IP address.
//Auth Laravel ka authentication system hai. Ye user ko login aur logout karne ke kaam aata hai.
//RateLimiter login attempts ko count aur limit karta hai, taake koi repeatedly wrong passwords try na kar sake.

class LoginController extends Controller
{
    public function show()
    {
        //shows login form to user
        return view('auth.login');
    }

    public function login(Request $request)
    {
        //recieves user request data 
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        // Har email aur IP ka alag lock
        $key = strtolower($request->email) . '|' . $request->ip();

        // 5 galat koshish ke baad 15 minute lock
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $minutes = ceil(RateLimiter::availableIn($key) / 60);
            return back()->withInput()->withErrors([
                'email' => 'Too many attempts. Try again in ' . $minutes . ' minute(s).',
            ]);
        }

        $login = Auth::attempt($request->only('email', 'password'));

        if (! $login) {
            RateLimiter::hit($key, 900); // 900 seconds = 15 minutes
            $left = 5 - RateLimiter::attempts($key);
            return back()->withInput()->withErrors([
                'email' => 'Email or password is wrong. ' . $left . ' attempt(s) left.',
            ]);
        }

        // Account band hai to bahar nikal do
        if (! Auth::user()->is_active) {
            Auth::logout();
            return back()->withInput()->withErrors([
                'email' => 'Your account is deactivated. Contact the admin.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
         //logout ka function he
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}