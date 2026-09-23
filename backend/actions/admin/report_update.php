<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_admin();
verify_csrf();
$id = (int) ($_POST['report_id'] ?? 0);
$status = (string) ($_POST['status'] ?? 'reviewing');
if (!in_array($status, ['open', 'reviewing', 'closed'], true)) $status = 'reviewing';
db()->prepare('UPDATE reports SET status = ? WHERE id = ?')->execute([$status, $id]);
flash('success', 'Bildirim durumu güncellendi.');
redirect(page_url('admin'));
