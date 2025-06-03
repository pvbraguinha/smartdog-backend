<?php

// 🔐 Força a APP_KEY antes de qualquer coisa
putenv("APP_KEY=base64:CdANHmCLLwnCYV7btlo6V/2qjNJ2ckiwh0fvLrkxjIQ=");
$_ENV['APP_KEY'] = 'base64:CdANHmCLLwnCYV7btlo6V/2qjNJ2ckiwh0fvLrkxjIQ=';
$_SERVER['APP_KEY'] = 'base64:CdANHmCLLwnCYV7btlo6V/2qjNJ2ckiwh0fvLrkxjIQ=';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

require_once dirname(__DIR__) . '/vendor/autoload.php';

// 🔧 Força uso do .env.production se existir
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
    ->create();


