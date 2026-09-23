<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/backend/config/env.php';
load_env($root . '/.env');

date_default_timezone_set((string) env('APP_TIMEZONE', 'Europe/Istanbul'));

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name((string) env('SESSION_NAME', 'sozfisi_session'));
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once $root . '/backend/config/database.php';
require_once $root . '/backend/helpers/functions.php';
require_once $root . '/backend/helpers/csrf.php';
require_once $root . '/backend/helpers/auth.php';
require_once $root . '/backend/helpers/agreements.php';
require_once $root . '/backend/helpers/uploads.php';

if ((bool) env('APP_DEBUG', false)) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
}
