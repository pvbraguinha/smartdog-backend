<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo 'DB_PASSWORD=' . getenv('DB_PASSWORD') . PHP_EOL;
echo 'DB_USERNAME=' . getenv('DB_USERNAME') . PHP_EOL;
echo 'DB_HOST=' . getenv('DB_HOST') . PHP_EOL;

