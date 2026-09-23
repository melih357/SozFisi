<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/bootstrap.php';
verify_csrf();

$token = trim((string) ($_POST['token'] ?? ''));
$agreement = find_agreement_by_token($token);
if (!$agreement) redirect(page_url('home'));
$guest = guest_party((int) $agreement['id']);
$status = (string) ($_POST['status'] ?? 'problem');
$allowed = ['completed', 'partial', 'problem'];
if (!in_array($status, $allowed, true)) $status = 'problem';
$note = trim((string) ($_POST['note'] ?? ''));

$stmt = db()->prepare("SELECT * FROM completions WHERE agreement_id = ? AND status = 'pending' ORDER BY id DESC LIMIT 1");
$stmt->execute([(int) $agreement['id']]);
$completion = $stmt->fetch();
if (!$completion) {
    flash('warning', 'Bekleyen bir tamamlama isteği bulunamadı.');
    redirect(page_url('invite', ['token' => $token]));
}

$newAgreementStatus = $status === 'completed' ? 'completed' : 'disputed';
db()->prepare("UPDATE completions SET status = ?, response_note = ?, responded_by_party_id = ?, responded_at = NOW() WHERE id = ?")
    ->execute([$status, $note !== '' ? $note : null, $guest['id'] ?? null, $completion['id']]);
db()->prepare("UPDATE agreements SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$newAgreementStatus, (int) $agreement['id']]);
log_event((int) $agreement['id'], 'completion_responded', null, $guest['display_name'] ?? 'Karşı taraf', [], ['status' => $status, 'note' => $note]);
flash($status === 'completed' ? 'success' : 'warning', $status === 'completed' ? 'Tamamlama kaydı oluşturuldu.' : 'Yanıtınız sorun/kısmi durum olarak kaydedildi.');
redirect(page_url('invite', ['token' => $token]));
