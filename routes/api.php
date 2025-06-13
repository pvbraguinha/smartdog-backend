<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\SnoutRecognitionController;
use App\Http\Controllers\DogLocationController;
use App\Http\Controllers\UserHistoryController;
use App\Http\Controllers\DogRegistrationController;
use App\Http\Controllers\PetHumanController;

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
Route::post('/upload-pet-images', [PetHumanController::class, 'upload']);

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

use Illuminate\Support\Facades\Storage;

Route::get('/public-gallery', function () {
    $baseUrl = config('app.url') . '/storage';

    $tipos = ['focinhos', 'frontais', 'angulos'];

    $galeria = [];

    foreach ($tipos as $tipo) {
        $arquivos = Storage::disk('public')->files("uploads/meupethumano/{$tipo}");
        $galeria[$tipo] = array_map(function ($path) use ($baseUrl) {
            return $baseUrl . '/' . $path;
        }, $arquivos);
    }

    return response()->json($galeria);
});

use App\Http\Controllers\PetTransformationController;

Route::post('/transform-pet', [PetTransformationController::class, 'transform']);

use Illuminate\Support\Facades\File;

Route::get('/debug-storage', function () {
    return [
        'exists_focinho' => File::exists(storage_path('app/public/uploads/meupethumano/focinhos')),
        'exists_frontais' => File::exists(storage_path('app/public/uploads/meupethumano/frontais')),
        'exists_angulos' => File::exists(storage_path('app/public/uploads/meupethumano/angulos')),
    ];
});

// Em routes/web.php ou routes/api.php
Route::get('/test-s3', function () {
    try {
        // Tenta listar arquivos no bucket
        $files = Storage::disk('s3')->files();
        return response()->json([
            'status' => 'S3 conectado com sucesso!',
            'files' => $files
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'Erro na conexão com S3',
            'error' => $e->getMessage()
        ], 500);
    }
});

Route::get('/debug-s3-vars', function () {
    return response()->json([
        'region'      => env('AWS_DEFAULT_REGION'),
        'bucket'      => env('AWS_BUCKET'),
        'access_key'  => env('AWS_ACCESS_KEY_ID'),
        'secret_set'  => !empty(env('AWS_SECRET_ACCESS_KEY')),
    ]);
});

Route::get('/debug-app-key', function () {
    return response()->json([
        'APP_KEY' => config('app.key'),
        'env_APP_KEY' => env('APP_KEY'),
        'APP_ENV' => config('app.env'),
    ]);
});

Route::get('/api/debug-replicate', function () {
    return response()->json([
        'token_ok' => config('services.replicate.token') ? true : false,
        'model_ok' => config('services.replicate.version') ? true : false,
        'token_prefix' => substr(config('services.replicate.token'), 0, 5),
    ]);
});











