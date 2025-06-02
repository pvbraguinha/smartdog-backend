<?php
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['message' => 'SmartDog API is working!']);
});

// 🔻 COMENTE ou REMOVA a rota antiga do reconhecimento
// Route::post('/snout-recognition', [SnoutRecognitionController::class, 'detect']);

// Rota simples para teste básico
Route::get('/hello', function () {
    return 'Hello from Laravel!';
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

// Rota para depurar APP_KEY em várias formas de acesso
Route::get('/check-env', function () {
    return response()->json([
        'APP_KEY_env' => env('APP_KEY', 'Não encontrada via env()'),
        'APP_KEY_getenv' => getenv('APP_KEY') ?: 'Não encontrada via getenv()',
        'APP_KEY_server' => $_SERVER['APP_KEY'] ?? 'Não encontrada via $_SERVER',
        'APP_KEY_env_array' => $_ENV['APP_KEY'] ?? 'Não encontrada via $_ENV',
    ]);
});

