<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['message' => 'SmartDog API is working!']);
});

// 🔻 COMENTE ou REMOVA a rota antiga do reconhecimento
// Route::post('/snout-recognition', [SnoutRecognitionController::class, 'detect']);

// Rota para debug da APP_KEY
Route::get('/debug-app-key', function () {
    return response()->json([
        'app_key' => env('APP_KEY'),
    ]);
});
