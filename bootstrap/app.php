<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Note: API routes skip CSRF by default in Laravel 12
        // No special configuration needed for api.php routes

        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'token-auth' => \App\Http\Middleware\TokenAuth::class,
            // Tambahkan alias middleware kustom lainnya di sini
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
