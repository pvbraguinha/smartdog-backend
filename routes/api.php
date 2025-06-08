<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\SnoutRecognitionController;
use App\Http\Controllers\DogLocationController;
use App\Http\Controllers\UserHistoryController;
use App\Http\Controllers\DogRegistrationController;

//  Teste simples de status da API
Route::get('/test', function () {
    return response()->json(['status' => 'API est no ar!']);
});

// routes/api.php
Route::post('/test-post', function () {
    return response()->json(['message' => 'POST funcionando']);
});

Route::get('/status', function () {
    return response()->json(['message' => 'SmartDog API is working!']);
});

//  Rotas da aplicao (sem autenticao por enquanto)
Route::post('/dogs', [DogRegistrationController::class, 'store']);
Route::post('/snout-recognition', [SnoutRecognitionController::class, 'detect']);
Route::post('/dogs/{id}/location', [DogLocationController::class, 'update']);
Route::get('/user/history', [UserHistoryController::class, 'index']);

//  Teste de APP_KEY (opcional)
Route::get('/app-key-test', function () {
    return response()->json([
        'APP_KEY' => env('APP_KEY', 'No encontrada'),
    ]);
});

//  Teste de conexo com banco de dados
Route::get('/test-db-connection', function (Request $request) {
    try {
        DB::connection()->getPdo();
        return response()->json([
            'message' => 'Conexo com banco OK',
            'db_host' => env('DB_HOST'),
            'db_username' => env('DB_USERNAME'),
            'db_password_set' => !empty(env('DB_PASSWORD')),
            'database_url' => env('DATABASE_URL'),
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'db_host' => env('DB_HOST'),
            'db_username' => env('DB_USERNAME'),
            'db_password_set' => !empty(env('DB_PASSWORD')),
            'database_url' => env('DATABASE_URL'),
        ], 500);
    }
});
Route::get('/check-env', function () {
    return response()->json([
        'DB_PASSWORD' => bin2hex(env('DB_PASSWORD')),
        'DB_USERNAME' => bin2hex(env('DB_USERNAME')),
    ]);
});








