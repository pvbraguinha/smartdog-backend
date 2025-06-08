<?php
require_once __DIR__.'/../vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$envPath = dirname(__DIR__);

// Carrega somente o .env normal
$dotenv = Dotenv\Dotenv::createImmutable($envPath);
$dotenv->safeLoad();

// Força APP_KEY para teste
putenv("APP_KEY=base64:CdANHmCLLwnCYV7btlo6V/2qjNJ2ckiwh0fvLrkxjIQ=");
$_ENV['APP_KEY'] = 'base64:CdANHmCLLwnCYV7btlo6V/2qjNJ2ckiwh0fvLrkxjIQ=';
$_SERVER['APP_KEY'] = 'base64:CdANHmCLLwnCYV7btlo6V/2qjNJ2ckiwh0fvLrkxjIQ=';

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



