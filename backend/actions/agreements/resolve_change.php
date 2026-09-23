<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_auth();
verify_csrf();

$agreementId = (int) ($_POST['agreement_id'] ?? 0);
$requestId = (int) ($_POST['request_id'] ?? 0);
$decision = (string) ($_POST['decision'] ?? 'reject');
$agreement = find_agreement_for_user($agreementId, (int) user()['id']);
if (!$agreement) redirect(page_url('dashboard'));

$stmt = db()->prepare("SELECT * FROM change_requests WHERE id = ? AND agreement_id = ? AND status = 'pending' LIMIT 1");
$stmt->execute([$requestId, $agreementId]);
$request = $stmt->fetch();
if (!$request) {
    flash('warning', 'Değişiklik talebi bulunamadı veya daha önce yanıtlandı.');
    redirect(page_url('agreement', ['id' => $agreementId]));
}

if ($decision === 'accept') {
    $version = current_version($agreementId);
    $content = json_decode_array($version['content_json'] ?? null);
    foreach ($content['clauses'] as &$clause) {
        if (($clause['key'] ?? '') === $request['clause_key']) {
            $clause['content'] = $request['requested_content'];
            $clause['source'] = 'mutual-change';
        }
    }
    unset($clause);

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $newVersion = create_version($agreementId, $content, (int) user()['id'], user()['name']);
        $party = $pdo->prepare("SELECT id FROM agreement_parties WHERE agreement_id = ? AND role = 'creator' LIMIT 1");
        $party->execute([$agreementId]);
        $creatorParty = $party->fetch();
        $pdo->prepare("INSERT INTO approvals (agreement_id, version_id, party_id, approval_type, method, approved_at, ip_hash, user_agent_hash) VALUES (?, ?, ?, 'creator', 'session', NOW(), ?, ?)")
            ->execute([$agreementId, $newVersion['id'], $creatorParty['id'], request_ip_hash(), request_agent_hash()]);
        $pdo->prepare("UPDATE change_requests SET status = 'accepted', resolved_at = NOW() WHERE id = ?")->execute([$requestId]);
        $pdo->prepare("UPDATE agreements SET status = 'sent', updated_at = NOW() WHERE id = ?")->execute([$agreementId]);
        log_event($agreementId, 'change_accepted', (int) user()['id'], user()['name'], ['request_id' => $requestId], ['version' => $newVersion['version_number']]);
        $pdo->commit();
        flash('success', 'Değişiklik kabul edildi. Yeni sürüm karşı tarafın onayına hazır.');
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
} else {
    db()->prepare("UPDATE change_requests SET status = 'rejected', resolved_at = NOW() WHERE id = ?")->execute([$requestId]);
    db()->prepare("UPDATE agreements SET status = 'sent', updated_at = NOW() WHERE id = ?")->execute([$agreementId]);
    log_event($agreementId, 'change_rejected', (int) user()['id'], user()['name'], ['request_id' => $requestId], []);
    flash('info', 'Değişiklik talebi reddedildi.');
}
redirect(page_url('agreement', ['id' => $agreementId]));
