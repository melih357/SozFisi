<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_guest();
verify_csrf();

$name = trim((string) ($_POST['name'] ?? ''));
$email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
$phone = trim((string) ($_POST['phone'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');
set_old($_POST);

$errors = [];
if (mb_strlen($name) < 2) $errors[] = 'Adınız en az 2 karakter olmalı.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Geçerli bir e-posta adresi yazın.';
if (strlen($password) < 8) $errors[] = 'Şifreniz en az 8 karakter olmalı.';
if ($password !== $passwordConfirmation) $errors[] = 'Şifreler birbiriyle uyuşmuyor.';

if ($errors === []) {
    $stmt = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) $errors[] = 'Bu e-posta adresi zaten kayıtlı.';
}

if ($errors !== []) {
    foreach ($errors as $error) flash('danger', $error);
    redirect(page_url('register'));
}

$stmt = db()->prepare("INSERT INTO users (name, email, phone, password_hash, role, plan, created_at, updated_at) VALUES (?, ?, ?, ?, 'user', 'free', NOW(), NOW())");
$stmt->execute([$name, $email, $phone !== '' ? $phone : null, password_hash($password, PASSWORD_DEFAULT)]);
$_SESSION['user_id'] = (int) db()->lastInsertId();
clear_old();
flash('success', 'Hesabınız oluşturuldu. İlk SözFişi’nizi hazırlayabilirsiniz.');
redirect(page_url('dashboard'));
