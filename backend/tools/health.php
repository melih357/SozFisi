<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
$result = [
    'app' => env('APP_NAME', 'SözFişi'),
    'php' => PHP_VERSION,
    'environment' => env('APP_ENV', 'local'),
    'database' => 'not_checked',
    'uploads_writable' => is_writable(dirname(__DIR__, 2) . '/uploads'),
    'time' => date(DATE_ATOM),
];
try {
    db()->query('SELECT 1');
    $result['database'] = 'ok';
} catch (Throwable $e) {
    $result['database'] = 'error';
    if ((bool) env('APP_DEBUG', false)) $result['database_message'] = $e->getMessage();
}
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
