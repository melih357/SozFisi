<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/services/OtpService.php';
verify_csrf();

$pending = $_SESSION['pending_otp'] ?? null;
$code = trim((string) ($_POST['code'] ?? ''));
if (!is_array($pending) || !preg_match('/^\d{6}$/', $code)) {
    flash('danger', 'Doğrulama kodu geçersiz.');
    redirect(page_url('verify', ['token' => (string) ($pending['token'] ?? '')]));
}

$otp = OtpService::verify((int) $pending['agreement_id'], (int) $pending['party_id'], (string) $pending['purpose'], $code);
if (!$otp) {
    flash('danger', 'Kod hatalı, süresi dolmuş veya çok fazla deneme yapıldı.');
    redirect(page_url('verify', ['token' => (string) $pending['token']]));
}

$agreementId = (int) $pending['agreement_id'];
$token = (string) $pending['token'];
$guest = guest_party($agreementId);

if (str_starts_with((string) $pending['purpose'], 'agreement_approval_v')) {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO approvals (agreement_id, version_id, party_id, approval_type, method, approved_at, ip_hash, user_agent_hash, otp_id) VALUES (?, ?, ?, 'guest', 'otp', NOW(), ?, ?, ?)");
        $stmt->execute([$agreementId, (int) $pending['version_id'], (int) $pending['party_id'], request_ip_hash(), request_agent_hash(), (int) $otp['id']]);
        $pdo->prepare("UPDATE agreement_versions SET frozen_at = NOW() WHERE id = ?")->execute([(int) $pending['version_id']]);
        $pdo->prepare("UPDATE agreement_parties SET verification_level = 'otp', verified_at = NOW(), invite_status = 'accepted' WHERE id = ?")->execute([(int) $pending['party_id']]);
        $pdo->prepare("UPDATE agreements SET status = 'active', updated_at = NOW() WHERE id = ?")->execute([$agreementId]);
        log_event($agreementId, 'agreement_approved', null, $guest['display_name'] ?? 'Karşı taraf', [], ['method' => 'otp']);
        $pdo->commit();
        flash('success', 'SözFişi onaylandı. Aynı son sürüm iki taraf için de donduruldu.');
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
} elseif (str_starts_with((string) $pending['purpose'], 'addendum_')) {
    $addendumId = (int) ($pending['addendum_id'] ?? 0);
    db()->prepare("UPDATE addendums SET status = 'approved', approved_at = NOW() WHERE id = ? AND agreement_id = ?")->execute([$addendumId, $agreementId]);
    log_event($agreementId, 'addendum_approved', null, $guest['display_name'] ?? 'Karşı taraf', [], ['addendum_id' => $addendumId]);
    flash('success', 'Ek SözFişi onaylandı.');
}

unset($_SESSION['pending_otp'], $_SESSION['debug_otp']);
redirect(page_url('invite', ['token' => $token]));
