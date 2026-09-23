<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_auth();
verify_csrf();

$id = (int) ($_POST['agreement_id'] ?? 0);
$agreement = find_agreement_for_user($id, (int) user()['id']);
if (!$agreement) {
    flash('danger', 'SözFişi bulunamadı.');
    redirect(page_url('dashboard'));
}

$stmt = db()->prepare("UPDATE agreements SET status = 'sent', updated_at = NOW() WHERE id = ?");
$stmt->execute([$id]);
db()->prepare("UPDATE agreement_parties SET invite_status = 'invited', invited_at = NOW() WHERE agreement_id = ? AND role = 'guest'")->execute([$id]);
log_event($id, 'invite_sent', (int) user()['id'], user()['name'], ['status' => $agreement['status']], ['status' => 'sent']);
flash('success', 'Davet bağlantısı hazırlandı. Bağlantıyı WhatsApp, SMS veya e-posta ile paylaşın.');
redirect(page_url('agreement', ['id' => $id]));
