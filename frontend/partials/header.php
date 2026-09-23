<?php
declare(strict_types=1);
$loggedIn = false;
$currentUser = null;
try {
    $loggedIn = auth_check();
    $currentUser = $loggedIn ? user() : null;
} catch (Throwable $e) {
    $loggedIn = false;
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0BAA9A">
    <meta name="description" content="SözFişi, konuşulan küçük anlaşmaları karşılıklı onaylanan ve takip edilebilen ortak kayda dönüştürür.">
    <title><?= e($pageTitle) ?> · <?= e((string) env('APP_NAME', 'SözFişi')) ?></title>
    <link rel="manifest" href="<?= e(url('frontend/manifest.webmanifest')) ?>">
    <link rel="icon" type="image/png" href="<?= e(url('frontend/assets/images/logo-icon.png')) ?>">
    <link rel="stylesheet" href="<?= e(url('frontend/assets/css/app.css')) ?>">
</head>
<body class="<?= $printMode ? 'print-mode' : '' ?>">
<?php if (!$printMode): ?>
<header class="site-header">
    <div class="container nav-wrap">
        <a class="brand" href="<?= e(page_url('home')) ?>" aria-label="SözFişi ana sayfa">
            <img src="<?= e(url('frontend/assets/images/logo-horizontal.png')) ?>" alt="SözFişi">
        </a>
        <button class="nav-toggle" type="button" aria-label="Menüyü aç" data-nav-toggle>☰</button>
        <nav class="main-nav" data-nav>
            <?php if ($loggedIn): ?>
                <a href="<?= e(page_url('dashboard')) ?>">SözFişlerim</a>
                <a href="<?= e(page_url('create')) ?>">Yeni oluştur</a>
                <a href="<?= e(page_url('profile')) ?>">Profil</a>
                <?php if (($currentUser['role'] ?? '') === 'admin'): ?>
                    <a href="<?= e(page_url('admin')) ?>">Yönetim</a>
                <?php endif; ?>
                <form method="post" action="<?= e(url('backend/actions/auth/logout.php')) ?>" class="inline-form">
                    <?= csrf_field() ?>
                    <button class="link-button" type="submit">Çıkış</button>
                </form>
            <?php else: ?>
                <a href="<?= e(page_url('home')) ?>#nasil-calisir">Nasıl çalışır?</a>
                <a href="<?= e(page_url('home')) ?>#kullanim">Kullanım alanları</a>
                <a href="<?= e(page_url('login')) ?>">Giriş yap</a>
                <a class="btn btn-primary btn-sm" href="<?= e(page_url('register')) ?>">Ücretsiz başla</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<?php endif; ?>
<main>
