<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

require_once dirname(__DIR__) . '/vendor/autoload.php';

// 🔥 Força uso do .env.production se existir
$envPath = dirname(__DIR__);

if (file_exists($envPath . '/.env.production')) {
    $dotenv = Dotenv\Dotenv::createImmutable($envPath, '.env.production');
} else {
    $dotenv = Dotenv\Dotenv::createImmutable($envPath);
}

$dotenv->safeLoad();

return Application::configure(basePath: $envPath)
    ->withRouting(
        web: $envPath . '/routes/web.php',
        commands: $envPath . '/routes/console.php',
        health: '/up'
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create(); // ← 🔥 Esse parêntese final provavelmente estava faltando

