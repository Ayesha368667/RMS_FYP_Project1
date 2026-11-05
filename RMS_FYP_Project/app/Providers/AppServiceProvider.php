<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Medicine;
use App\Models\DoctorAssignment;
use App\Observers\ActivityObserver;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        User::observe(ActivityObserver::class);
        Patient::observe(ActivityObserver::class);
        Doctor::observe(ActivityObserver::class);
        Medicine::observe(ActivityObserver::class);
        DoctorAssignment::observe(ActivityObserver::class);
    }
}
