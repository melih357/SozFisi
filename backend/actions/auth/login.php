<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_guest();
verify_csrf();

$email = trim((string) ($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
set_old(['email' => $email]);

if (!attempt_login($email, $password)) {
    flash('danger', 'E-posta veya şifre hatalı.');
    redirect(page_url('login'));
}

clear_old();
flash('success', 'Tekrar hoş geldiniz.');
redirect(page_url('dashboard'));
