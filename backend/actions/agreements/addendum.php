<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_auth();
verify_csrf();

$agreementId = (int) ($_POST['agreement_id'] ?? 0);
$agreement = find_agreement_for_user($agreementId, (int) user()['id']);
if (!$agreement) redirect(page_url('dashboard'));

$title = trim((string) ($_POST['title'] ?? 'Ek SözFişi'));
$reason = trim((string) ($_POST['reason'] ?? ''));
$amount = str_replace(',', '.', preg_replace('/[^0-9,.]/', '', (string) ($_POST['additional_amount'] ?? '')) ?? '');
$newEndDate = trim((string) ($_POST['new_end_date'] ?? ''));
if ($reason === '') {
    flash('danger', 'Ek işin veya değişikliğin nedenini yazın.');
    redirect(page_url('agreement', ['id' => $agreementId]));
}

$stmt = db()->prepare("INSERT INTO addendums (agreement_id, title, reason, additional_amount, new_end_date, status, invite_token, created_by_user_id, created_at) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, NOW())");
$stmt->execute([$agreementId, $title, $reason, $amount !== '' ? (float) $amount : null, $newEndDate !== '' ? $newEndDate : null, random_token(20), (int) user()['id']]);
log_event($agreementId, 'addendum_created', (int) user()['id'], user()['name'], [], ['title' => $title]);
flash('success', 'Ek SözFişi oluşturuldu ve karşı tarafın ekranına eklendi.');
redirect(page_url('agreement', ['id' => $agreementId]));
