<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_auth();
verify_csrf();

$agreementId = (int) ($_POST['agreement_id'] ?? 0);
$agreement = find_agreement_for_user($agreementId, (int) user()['id']);
if (!$agreement) redirect(page_url('dashboard'));

try {
    $stored = store_uploaded_file($_FILES['attachment'] ?? [], $agreementId);
    $stmt = db()->prepare("INSERT INTO attachments (agreement_id, uploaded_by_user_id, original_name, stored_name, mime_type, size_bytes, sha256, visibility, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'both', NOW())");
    $stmt->execute([$agreementId, (int) user()['id'], $stored['original_name'], $stored['stored_name'], $stored['mime_type'], $stored['size_bytes'], $stored['sha256']]);
    log_event($agreementId, 'attachment_uploaded', (int) user()['id'], user()['name'], [], ['name' => $stored['original_name'], 'sha256' => $stored['sha256']]);
    flash('success', 'Dosya güvenli şekilde yüklendi.');
} catch (Throwable $e) {
    flash('danger', $e->getMessage());
}
redirect(page_url('agreement', ['id' => $agreementId]));
