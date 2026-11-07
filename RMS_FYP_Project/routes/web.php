<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ForcePasswordController;
use App\Http\Controllers\Auth\ForgotPasswordController;

// Homepage
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Login aur forgot/reset password (sirf wo log jo login nahi hain)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.store');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendLink'])->name('password.email');
    Route::get('/reset-password/{token}', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ForgotPasswordController::class, 'reset'])->name('password.update');
});

// Logout
Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Password change
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/password/change', [ForcePasswordController::class, 'show'])->name('password.change');
    Route::put('/password/change', [ForcePasswordController::class, 'update'])->name('password.change.update');
});

// Role ke hisab se dashboard
Route::middleware(['auth', 'active', 'password.changed'])->get('/dashboard', function () {
    return match (auth()->user()->role) {
        'admin'   => redirect('/admin'),
        'doctor'  => redirect('/doctor'),
        'patient' => redirect()->route('patient.dashboard'),
        default   => abort(403),
    };
})->name('dashboard');

// Patient portal (C ke pages yahan aayenge)
Route::middleware(['auth', 'active', 'password.changed', 'role:patient'])->group(function () {
    Route::get('/patient/dashboard', function () {
        return view('patient.dashboard');
    })->name('patient.dashboard');
});

// Language switcher (en/ur)
Route::get('/lang/{locale}', function ($locale) {
    abort_unless(in_array($locale, ['en', 'ur']), 404);
    session(['locale' => $locale]);
    return redirect()->back();
})->name('lang.switch');