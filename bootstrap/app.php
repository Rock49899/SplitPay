<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permission'      => \App\Http\Middleware\CheckPermission::class,
            'active.account'  => \App\Http\Middleware\EnsureAccountActive::class,
            'active.annexe'   => \App\Http\Middleware\SetActiveAnnexe::class,
            'active.school_year' => \App\Http\Middleware\SetActiveSchoolYear::class,
            'school_year.lock' => \App\Http\Middleware\EnforceSchoolYearAccess::class,
            'student.auth'    => \App\Http\Middleware\StudentAuthMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
