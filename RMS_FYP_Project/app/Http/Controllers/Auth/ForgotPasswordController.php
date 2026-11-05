<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class ForgotPasswordController extends Controller
{
    public function showLinkForm()
    {
        return view('auth.forgot-password');
    }

    public function sendLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        // Link sirf active account ko jayega
        if ($user && $user->is_active) {
            Password::sendResetLink($request->only('email'));
        }

        // Hamesha same message, taake koi email guess na kar sake
        return back()->with('status', 'If this email is registered, a reset link has been sent.');
    }

    public function showResetForm(Request $request, $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);

        $user = User::where('email', $request->email)->first();

        // Purana password dobara nahi chalega
        if ($user && $user->isOldPassword($request->password)) {
            return back()->withInput($request->only('email'))->withErrors([
                'password' => 'You cannot reuse your current or previous password.',
            ]);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->previous_password = $user->password;
                $user->password = Hash::make($password);
                $user->must_change_password = false;
                $user->setRememberToken(Str::random(60));
                $user->save();
            }
        );

        if ($status != Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'This reset link is invalid or has expired.',
            ]);
        }

        return redirect()->route('login')->with('status', 'Password changed. Please login.');
    }
}