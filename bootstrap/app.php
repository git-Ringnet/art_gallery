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
        
        // Đăng ký middleware alias
        $middleware->alias([
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'archive.readonly' => \App\Http\Middleware\CheckArchiveMode::class,
        ]);
        
        // Exclude route upload-images-batch khỏi CSRF verification
        // (vì sau khi import SQL, session có thể thay đổi)
        $middleware->validateCsrfTokens(except: [
            'year/upload-images-batch',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
