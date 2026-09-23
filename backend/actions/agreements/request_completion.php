<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_auth();
verify_csrf();

$agreementId = (int) ($_POST['agreement_id'] ?? 0);
$agreement = find_agreement_for_user($agreementId, (int) user()['id']);
if (!$agreement || !in_array($agreement['status'], ['approved', 'active'], true)) {
    flash('warning', 'Bu SözFişi için tamamlama isteği gönderilemiyor.');
    redirect(page_url('agreement', ['id' => $agreementId]));
}

$note = trim((string) ($_POST['note'] ?? 'İş tamamlandı.'));
db()->prepare("INSERT INTO completions (agreement_id, requested_by_user_id, request_note, status, created_at) VALUES (?, ?, ?, 'pending', NOW())")->execute([$agreementId, (int) user()['id'], $note]);
db()->prepare("UPDATE agreements SET status = 'completion_pending', updated_at = NOW() WHERE id = ?")->execute([$agreementId]);
log_event($agreementId, 'completion_requested', (int) user()['id'], user()['name'], [], ['note' => $note]);
flash('success', 'Tamamlama onayı karşı tarafa gönderildi.');
redirect(page_url('agreement', ['id' => $agreementId]));
