<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            //admin just apny notifications dekh skta he bcz id match hony pr hi relevant notification dikhty hen
            //admin panel me bell icon lgati he
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            //bell hr 30 seconds me khud notification
            //check krta rehta he koi new to ni aya jo show krna he.

            ->path('admin')
            
            ->colors(['primary' => 
                \Filament\Support\Colors\Color::Teal
            ])

            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\Filament\Admin\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                //directly admin panel in classes ko check kry ga authentication ky lye 
                \Filament\Http\Middleware\Authenticate::class,
                \App\Http\Middleware\EnsureAccountIsActive::class,
                \App\Http\Middleware\EnsurePasswordChanged::class,
            ]);
    }
}

//bootstrap/app.php aliases register karta hai.
//Routes mein active jaisa short name use kar sakti ho.
//Filament panel ke middleware stack mein class name use karo.
