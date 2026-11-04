
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ForcePasswordController;

// Login page
Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return app(LoginController::class)->show();
    })->name('login');

    Route::post('/login', function (Illuminate\Http\Request $request) {
        return app(LoginController::class)->login($request);
    })->name('login.store');
});

// Patient dashboard (layout)
Route::middleware(['auth', 'active', 'password.changed', 'role:patient'])
    ->get('/patient/dashboard', fn () => view('patient.dashboard'))
    ->name('patient.dashboard');

// Logout
Route::post('/logout', function (Illuminate\Http\Request $request) {
    return app(LoginController::class)->logout($request);
})->middleware('auth')->name('logout');


// Homepage
Route::get('/', function () {
    return redirect()->route('dashboard');
});


// Password change routes
Route::middleware(['auth', 'active'])->group(function () {

    Route::get('/password/change', function () {
        return app(ForcePasswordController::class)->show();
    })->name('password.change');

    Route::put('/password/change', function (Illuminate\Http\Request $request) {
        return app(ForcePasswordController::class)->update($request);
    })->name('password.change.update');

});


// Role-based dashboard
Route::middleware(['auth', 'active', 'password.changed'])
    ->get('/dashboard', function () {

        return match (auth()->user()->role) {
            'admin'   => redirect('/admin'),
            'doctor'  => redirect('/doctor'),
            'patient' => redirect()->route('patient.dashboard'),
            default   => abort(403),
        };

    })->name('dashboard');


// Language switcher (en/ur)
Route::get('/lang/{locale}', function (string $locale) {
    abort_unless(in_array($locale, ['en', 'ur'], true), 404);
    session(['locale' => $locale]);
    return redirect()->back();
})->name('lang.switch');