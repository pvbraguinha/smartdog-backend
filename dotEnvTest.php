<?php
require __DIR__ . '/vendor/autoload.php';

// usa unsafe para permitir getenv() também
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__);
$dotenv->load();

echo 'DB_HOST=' . ($_ENV['DB_HOST'] ?? 'não definido') . PHP_EOL;
echo 'DB_USERNAME=' . ($_ENV['DB_USERNAME'] ?? 'não definido') . PHP_EOL;
echo 'DB_PASSWORD=' . ($_ENV['DB_PASSWORD'] ?? 'não definido') . PHP_EOL;
echo 'DATABASE_URL=' . ($_ENV['DATABASE_URL'] ?? 'não definido') . PHP_EOL;









