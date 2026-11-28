<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PatientProfileController extends Controller
{
    public function show()
    {
        $user    = auth()->user();
        $patient = Patient::where('user_id', $user->id)->first();

        return view('patient.profile', compact('user', 'patient'));
    }

    public function update(Request $request)
    {
        // Spaces aur dashes hata do (0300 123-4567 bhi chale)
        $request->merge([
            'phone' => preg_replace('/[\s\-]/', '', (string) $request->input('phone')) ?: null,
        ]);

        $data = $request->validate([
            'phone'              => ['nullable', 'regex:/^(\+92|92|0)?3\d{9}$/'],
            'preffered_language' => ['required', Rule::in(['en', 'ur'])],
        ], [
            'phone.regex' => __('profile.phone_invalid'),
        ]);

        // Mass assignment ke bajaye seedha set karein (User model me fillable do jagah likha hai)
        $user = auth()->user();
        $user->phone = $data['phone'] ?? null;
        $user->preffered_language = $data['preffered_language'];
        $user->save();

        // Middleware session ko pehle dekhta hai, is liye session bhi badlein
        session(['locale' => $data['preffered_language']]);

        return redirect()->route('patient.profile')
            ->with('success', __('profile.saved', [], $data['preffered_language']));
    }
}