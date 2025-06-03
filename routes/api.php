<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\SnoutRecognitionController;
use App\Http\Controllers\DogLocationController;
use App\Http\Controllers\UserHistoryController;
use App\Http\Controllers\DogRegistrationController;

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

// Rota para testar conexão com banco de dados e variáveis relacionadas
Route::get('/test-db-connection', function (Request $request) {
    try {
        DB::connection()->getPdo();
        return response()->json([
            'message' => 'Conexão com banco OK',
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




