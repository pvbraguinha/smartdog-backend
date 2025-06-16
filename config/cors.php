<?php

return [

    'paths' => [
        'api/*',
        'upload-pet-images', // sua rota customizada
        '*', // opcional, para garantir que tudo funcione no MVP
    ],

    'allowed_methods' => ['*'],

    // Se quiser deixar aberto para qualquer origem durante a campanha/teste
    'allowed_origins' => ['*'],

    // Alternativa mais segura:
    // 'allowed_origins' => ['https://SEU_FRONTEND_DOMAIN_HERE'], 

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
