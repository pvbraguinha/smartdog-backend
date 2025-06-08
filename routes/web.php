<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SnoutRecognitionController;
use App\Http\Controllers\DogLocationController;
use App\Http\Controllers\UserHistoryController;
use App\Http\Controllers\DogRegistrationController;

Route::get('/', function () {
    return response()->json(['message' => 'SmartDog API is working!']);
});

// Rotas principais do sistema
Route::post('/snout-recognition', [SnoutRecognitionController::class, 'detect']);
Route::post('/dogs/{id}/location', [DogLocationController::class, 'update']);
Route::get('/user/history', [UserHistoryController::class, 'index']);
Route::post('/dogs', [DogRegistrationController::class, 'store']);

// Rota para testar APP_KEY no ambiente
Route::get('/app-key-test', function () {
    return response()->json([
        'APP_KEY' => env('APP_KEY', 'Não encontrada'),
    ]);
});

// Rota simples para teste básico
Route::get('/ping', function () {
    return response()->json(['message' => 'pong']);
});

// Rota para debug da APP_KEY
Route::get('/debug-app-key', function () {
    return response()->json([
        'app_key' => env('APP_KEY', 'Não encontrada'),
    ]);
});

// Rota para limpar cache do Laravel via web
Route::get('/clear-cache', function () {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('config:cache');
    return 'Cache limpo!';
});

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['message' => 'SmartDog API online. Utilize /api/ para acessar os endpoints.']);
});

// Rota para depurar APP_KEY e variáveis de ambiente
Route::get('/check-env', function () {
    return response()->json([
        'APP_ENV' => env('APP_ENV', 'não definida'),
        'APP_KEY_env' => env('APP_KEY', 'Não encontrada via env()'),
        'APP_KEY_getenv' => getenv('APP_KEY') ?: 'Não encontrada via getenv()',
        'APP_KEY_server' => $_SERVER['APP_KEY'] ?? 'Não encontrada via $_SERVER',
        'APP_KEY_env_array' => $_ENV['APP_KEY'] ?? 'Não encontrada via $_ENV',
        'APP_DEBUG' => env('APP_DEBUG', 'não definido'),
        'APP_URL' => env('APP_URL', 'não definida'),
    ]);
});

// Rota para mostrar a chave diretamente da config
Route::get('/check-key', function () {
    return config('app.key');
});
