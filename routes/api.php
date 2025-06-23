<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use App\Http\Controllers\SnoutRecognitionController;
use App\Http\Controllers\DogLocationController;
use App\Http\Controllers\UserHistoryController;
use App\Http\Controllers\DogRegistrationController;
use App\Http\Controllers\PetHumanController;
use App\Http\Controllers\PetTransformationController;
use App\Http\Controllers\SnoutCompareController;

Route::get('/test', fn() => response()->json(['status' => 'API está no ar!']));
Route::post('/test-post', fn() => response()->json(['message' => 'POST funcionando']));
Route::get('/status', fn() => response()->json(['message' => 'SmartDog API is working!']));

// Rotas principais
Route::post('/snout-recognition', [SnoutRecognitionController::class, 'recognize']);
Route::post('/dogs', [DogRegistrationController::class, 'store']);
Route::patch('/dogs/{id}/location', [DogLocationController::class, 'update']);
Route::get('/user/history', [UserHistoryController::class, 'index']);
Route::post('/upload-pet-images', [PetHumanController::class, 'upload']);
Route::post('/transform-pet', [PetTransformationController::class, 'transform']);

Route::get('/app-key-test', fn() => response()->json(['APP_KEY' => env('APP_KEY', 'Não encontrada')]));

Route::get('/test-db-connection', function () {
    try {
        DB::connection()->getPdo();
        return response()->json([
            'message' => 'Conexão com banco OK',
            'db_host' => env('DB_HOST'),
            'db_username' => env('DB_USERNAME'),
            'db_password_set' => !empty(env('DB_PASSWORD')),
            'database_url' => env('DATABASE_URL'),
        ]);
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

Route::get('/check-env', fn() => response()->json([
    'DB_PASSWORD' => bin2hex(env('DB_PASSWORD')),
    'DB_USERNAME' => bin2hex(env('DB_USERNAME')),
]));

Route::get('/public-gallery', function () {
    $baseUrl = config('app.url') . '/storage';
    $tipos = ['focinhos', 'frontais', 'angulos'];
    $galeria = [];

    foreach ($tipos as $tipo) {
        $arquivos = Storage::disk('public')->files("uploads/meupethumano/{$tipo}");
        $galeria[$tipo] = array_map(fn($path) => $baseUrl . '/' . $path, $arquivos);
    }

    return response()->json($galeria);
});

Route::get('/debug-storage', fn() => [
    'exists_focinho' => File::exists(storage_path('app/public/uploads/meupethumano/focinhos')),
    'exists_frontais' => File::exists(storage_path('app/public/uploads/meupethumano/frontais')),
    'exists_angulos' => File::exists(storage_path('app/public/uploads/meupethumano/angulos')),
]);

Route::get('/test-s3', function () {
    try {
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

Route::get('/debug-s3-vars', fn() => response()->json([
    'region'      => env('AWS_DEFAULT_REGION'),
    'bucket'      => env('AWS_BUCKET'),
    'access_key'  => env('AWS_ACCESS_KEY_ID'),
    'secret_set'  => !empty(env('AWS_SECRET_ACCESS_KEY')),
]));

Route::get('/debug-app-key', fn() => response()->json([
    'APP_KEY' => config('app.key'),
    'env_APP_KEY' => env('APP_KEY'),
    'APP_ENV' => config('app.env'),
]));

Route::get('/api/debug-replicate', fn() => response()->json([
    'token_ok' => config('services.replicate.token') ? true : false,
    'model_ok' => config('services.replicate.version') ? true : false,
    'token_prefix' => substr(config('services.replicate.token'), 0, 5),
]));

Route::get('/pet-human-count', function () {
    try {
        $tipos = ['frontal', 'focinho'];
        $total = 0;
        foreach ($tipos as $tipo) {
            $arquivos = Storage::disk('s3')->files("uploads/meupethumano/{$tipo}");
            $total += count($arquivos);
        }
        return response()->json(['count' => $total]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Erro ao contar imagens', 'message' => $e->getMessage()], 500);
    }
});

Route::post('/snout-compare', [SnoutCompareController::class, 'compare']);

// Corrigido para buscar imagens nas subpastas do bucket
Route::get('/s3/focinhos-smartdog', function () {
    try {
        $allFiles = [];
        $baseFolder = 'uploads/meu-pet-humano-imagens/focinhos-smartdog';

        // Listar todas as subpastas dentro da pasta base
        $folders = Storage::disk('s3')->directories($baseFolder);

        // Para cada subpasta, pega os arquivos e adiciona ao array
        foreach ($folders as $folder) {
            $files = Storage::disk('s3')->files($folder);
            $allFiles = array_merge($allFiles, $files);
        }

        // Gerar URLs públicas para todos os arquivos encontrados
        $urls = array_map(fn($path) => Storage::disk('s3')->url($path), $allFiles);

        return response()->json([
            'success' => true,
            'count' => count($urls),
            'images' => $urls,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erro ao acessar o S3',
            'error' => $e->getMessage(),
        ], 500);
    }
});
