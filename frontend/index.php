<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/backend/bootstrap.php';

$page = (string) ($_GET['page'] ?? 'home');
$routes = [
    'home' => 'home.php',
    'login' => 'login.php',
    'register' => 'register.php',
    'dashboard' => 'dashboard.php',
    'create' => 'create.php',
    'agreement' => 'agreement.php',
    'invite' => 'invite.php',
    'verify' => 'verify.php',
    'profile' => 'profile.php',
    'admin' => 'admin.php',
    'privacy' => 'privacy.php',
    'terms' => 'terms.php',
];

if (!isset($routes[$page])) {
    http_response_code(404);
    $page = 'not-found';
    $view = __DIR__ . '/pages/not-found.php';
} else {
    $view = __DIR__ . '/pages/' . $routes[$page];
}

$pageTitles = [
    'home' => 'Konuştuklarınızı netleştirin',
    'login' => 'Giriş yap',
    'register' => 'Hesap oluştur',
    'dashboard' => 'SözFişlerim',
    'create' => 'Yeni SözFişi',
    'agreement' => 'SözFişi detayı',
    'invite' => 'SözFişi daveti',
    'verify' => 'Doğrulama',
    'profile' => 'Profil',
    'admin' => 'Yönetim paneli',
    'privacy' => 'Gizlilik',
    'terms' => 'Kullanım koşulları',
    'not-found' => 'Sayfa bulunamadı',
];
$pageTitle = $pageTitles[$page] ?? 'SözFişi';
$printMode = isset($_GET['print']) && $_GET['print'] === '1';

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/flash.php';
require $view;
require __DIR__ . '/partials/footer.php';
