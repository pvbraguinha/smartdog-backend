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
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',

