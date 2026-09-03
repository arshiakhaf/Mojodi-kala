<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

echo json_encode([
    'ok' => true,
    'service' => 'mojodi-kala',
    'environment' => 'catalog',
    'database' => app_db() instanceof PDO ? 'sqlite' : 'fallback-data',
    'time' => date(DATE_ATOM),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
