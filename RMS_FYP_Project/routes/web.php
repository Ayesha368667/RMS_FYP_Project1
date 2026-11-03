<?php

use Illuminate\Support\Facades\Route;

// Temporary: Day 4 replaces this with the real login page
//login page 
Route::get('/login', fn () => redirect('/admin/login'))->name('login');
//dashboard after opening website
Route::get('/', fn () => redirect()->route('dashboard'));
//role based access to relative dashboard
Route::middleware(['auth', 'active'])->get('/dashboard', function () {
    return match (auth()->user()->role) {
        'admin'   => redirect('/admin'),
        'doctor'  => redirect('/doctor'),
        'patient' => redirect()->route('patient.dashboard'),
        default   => abort(403),
    };
})->name('dashboard');

// Placeholder: Member C replaces this with his real patient dashboard
Route::middleware(['auth', 'active', 'role:patient'])
    ->get('/patient/dashboard', fn () => 'Patient dashboard (placeholder)')
    ->name('patient.dashboard');
    // Placeholder: Member C replaces this with his real patient dashboard
Route::middleware(['auth', 'active', 'role:patient'])
    ->get('/patient/dashboard', fn () => view('patient.dashboard'))
    ->name('patient.dashboard');
    // DEV ONLY: sirf local machine par chalta hai, patient portal par kaam karne ke liye
if (app()->environment('local')) {
    Route::get('/dev/login-patient', function () {
        auth()->login(\App\Models\User::where('email', 'patient@test.com')->firstOrFail());
        return redirect()->route('patient.dashboard');
    });
}