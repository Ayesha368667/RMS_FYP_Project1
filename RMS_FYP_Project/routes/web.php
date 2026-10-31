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