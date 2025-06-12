<?php

use App\Services\PetTransformationService;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = app()->make(PetTransformationService::class);

$result = $service->transformPet([
    'frontal' => 'https://meu-pet-humano-imagens.s3.eu-north-1.amazonaws.com/uploads/meupethumano/focinhos/eYXeiIMvkvIxBuDYBQ3qiH4bK2zeaWj3Bsa0Pkxt.jpg'
], 'sessao_teste_001', 'golden retriever');

print_r($result);
