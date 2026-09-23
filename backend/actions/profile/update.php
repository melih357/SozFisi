<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_auth();
verify_csrf();

$name = trim((string) ($_POST['name'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? ''));
if (mb_strlen($name) < 2) {
    flash('danger', 'Adınız en az 2 karakter olmalı.');
    redirect(page_url('profile'));
}

db()->prepare('UPDATE users SET name = ?, phone = ?, updated_at = NOW() WHERE id = ?')->execute([$name, $phone !== '' ? $phone : null, (int) user()['id']]);
flash('success', 'Profil bilgileriniz güncellendi.');
redirect(page_url('profile'));
