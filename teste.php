<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

echo getenv('DB_PASSWORD') ?: 'variável DB_PASSWORD não encontrada';
