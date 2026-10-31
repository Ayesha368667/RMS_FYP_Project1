<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    //withmiddleware btata he ky mjhy is middleware ki configuration krni he
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            //alias mtlb middleware ko shortname dena
            //mtlb role sy hm EnsureRole ki class ko access kren gy or wo chalen gy
            'role'   => \App\Http\Middleware\EnsureRole::class,
            'active' => \App\Http\Middleware\EnsureAccountIsActive::class,
        //agr hm ye ni bnaty to hmy pury class reference sy access krna prta he it gives short name
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
