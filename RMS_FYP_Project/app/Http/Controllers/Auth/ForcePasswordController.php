<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
//Password ko securely hash (encrypted jaisa one-way process) karne ke liye use hota hai.
use Illuminate\Validation\Rules\Password;
//implements laravel's password rules
class ForcePasswordController extends Controller
{
    public function show()
    {
        //shows change password page
        return view('auth.change-password');
    }

    public function update(Request $request)
    {
        //
        $request->validate([
            //ye confirm kry ga ky admin provided password enter kro taky kisi or ka change na kr sko 
            'current_password' => ['required', 'current_password'],
            'password' => [//new password

                'required', 'confirmed',
                Password::min(8)->letters()->numbers(),
            ],
        ]);

        $user = $request->user();

        if ($user->isOldPassword($request->password)) {
            return back()->withErrors([
                'password' => 'You cannot reuse your current or previous password.',
            ]);
     }

        $user->previous_password = $user->password;

        $user->password = Hash::make($request->password);
        $user->must_change_password = false;
        $user->save();
        //redirect to dashboard after successful password change
        return redirect()->route('dashboard')->with('status', 'Password changed.');
    }
}
