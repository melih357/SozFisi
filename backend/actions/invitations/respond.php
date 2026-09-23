<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/services/OtpService.php';
verify_csrf();

$token = trim((string) ($_POST['token'] ?? ''));
$decision = (string) ($_POST['decision'] ?? '');
$agreement = find_agreement_by_token($token);
if (!$agreement) {
    flash('danger', 'Davet bağlantısı geçersiz veya süresi dolmuş.');
    redirect(page_url('home'));
}
$guest = guest_party((int) $agreement['id']);
$version = current_version((int) $agreement['id']);
if (!$guest || !$version) {
    flash('danger', 'SözFişi bilgileri eksik.');
    redirect(page_url('home'));
}

if ($decision === 'accept') {
    $purpose = 'agreement_approval_v' . (int) $version['version_number'];
    $code = OtpService::issue((int) $agreement['id'], (int) $guest['id'], $purpose);
    $_SESSION['pending_otp'] = [
        'agreement_id' => (int) $agreement['id'],
        'party_id' => (int) $guest['id'],
        'purpose' => $purpose,
        'token' => $token,
        'version_id' => (int) $version['id'],
    ];
    if ((bool) env('OTP_DEBUG', false)) {
        $_SESSION['debug_otp'] = $code;
    }
    log_event((int) $agreement['id'], 'otp_requested', null, $guest['display_name'], [], ['purpose' => $purpose]);
    redirect(page_url('verify', ['token' => $token]));
}

if ($decision === 'change') {
    $clauseKey = trim((string) ($_POST['clause_key'] ?? ''));
    $requestedContent = trim((string) ($_POST['requested_content'] ?? ''));
    $note = trim((string) ($_POST['note'] ?? ''));
    $clauses = version_clauses((int) $version['id']);
    $selected = null;
    foreach ($clauses as $clause) {
        if ($clause['clause_key'] === $clauseKey) {
            $selected = $clause;
            break;
        }
    }
    if (!$selected || $requestedContent === '') {
        flash('danger', 'Değiştirmek istediğiniz maddeyi ve yeni önerinizi yazın.');
        redirect(page_url('invite', ['token' => $token]));
    }
    $stmt = db()->prepare("INSERT INTO change_requests (agreement_id, version_id, party_id, clause_key, old_content, requested_content, note, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
    $stmt->execute([(int) $agreement['id'], (int) $version['id'], (int) $guest['id'], $clauseKey, $selected['content'], $requestedContent, $note !== '' ? $note : null]);
    db()->prepare("UPDATE agreements SET status = 'changes_requested', updated_at = NOW() WHERE id = ?")->execute([(int) $agreement['id']]);
    log_event((int) $agreement['id'], 'change_requested', null, $guest['display_name'], ['content' => $selected['content']], ['content' => $requestedContent]);
    flash('success', 'Değişiklik talebiniz oluşturucuya iletildi.');
    redirect(page_url('invite', ['token' => $token]));
}

if ($decision === 'not_mine') {
    db()->prepare("UPDATE agreement_parties SET invite_status = 'not_mine' WHERE id = ?")->execute([(int) $guest['id']]);
    db()->prepare("INSERT INTO reports (agreement_id, reporter_name, reporter_email, type, description, status, created_at) VALUES (?, ?, ?, 'not_mine', ?, 'open', NOW())")
        ->execute([(int) $agreement['id'], (string) $guest['display_name'], $guest['contact_type'] === 'email' ? $guest['contact_value'] : null, 'Bu anlaşmanın bana ait olmadığını bildirdim.']);
    log_event((int) $agreement['id'], 'not_mine_reported', null, $guest['display_name']);
    flash('success', 'Bildirim alındı. Bu davet size ait değil olarak işaretlendi.');
    redirect(page_url('home'));
}

if ($decision === 'approve_addendum') {
    $addendumId = (int) ($_POST['addendum_id'] ?? 0);
    $stmt = db()->prepare("SELECT * FROM addendums WHERE id = ? AND agreement_id = ? AND status = 'pending' LIMIT 1");
    $stmt->execute([$addendumId, (int) $agreement['id']]);
    $addendum = $stmt->fetch();
    if (!$addendum) {
        flash('warning', 'Ek SözFişi bulunamadı.');
        redirect(page_url('invite', ['token' => $token]));
    }
    $purpose = 'addendum_' . $addendumId;
    $code = OtpService::issue((int) $agreement['id'], (int) $guest['id'], $purpose);
    $_SESSION['pending_otp'] = [
        'agreement_id' => (int) $agreement['id'],
        'party_id' => (int) $guest['id'],
        'purpose' => $purpose,
        'token' => $token,
        'version_id' => (int) $version['id'],
        'addendum_id' => $addendumId,
    ];
    if ((bool) env('OTP_DEBUG', false)) $_SESSION['debug_otp'] = $code;
    redirect(page_url('verify', ['token' => $token]));
}

flash('danger', 'Geçersiz işlem.');
redirect(page_url('invite', ['token' => $token]));
