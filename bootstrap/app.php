<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Thêm middleware kiểm tra trạng thái user vào group 'web'
        $middleware->appendToGroup('web', \App\Http\Middleware\CheckUserActive::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
