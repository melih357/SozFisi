<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/services/AgreementAnalyzer.php';
require_auth();
verify_csrf();

$type = (string) ($_POST['type'] ?? 'other');
$allowedTypes = ['service', 'purchase_service', 'sale', 'debt', 'loan_item', 'shared_expense', 'deposit', 'other'];
if (!in_array($type, $allowedTypes, true)) $type = 'other';

$title = trim((string) ($_POST['title'] ?? ''));
$guestName = trim((string) ($_POST['guest_name'] ?? ''));
$guestContact = trim((string) ($_POST['guest_contact'] ?? ''));
$rawText = trim((string) ($_POST['raw_text'] ?? ''));
$totalAmount = str_replace(',', '.', preg_replace('/[^0-9,.]/', '', (string) ($_POST['total_amount'] ?? '')) ?? '');
$explicit = [
    'description' => trim((string) ($_POST['description'] ?? '')),
    'payment_terms' => trim((string) ($_POST['payment_terms'] ?? '')),
    'included_items' => trim((string) ($_POST['included_items'] ?? '')),
    'excluded_items' => trim((string) ($_POST['excluded_items'] ?? '')),
    'revision_terms' => trim((string) ($_POST['revision_terms'] ?? '')),
    'cancellation_terms' => trim((string) ($_POST['cancellation_terms'] ?? '')),
    'notes' => trim((string) ($_POST['notes'] ?? '')),
    'start_date' => trim((string) ($_POST['start_date'] ?? '')),
    'end_date' => trim((string) ($_POST['end_date'] ?? '')),
    'total_amount' => $totalAmount,
];
set_old($_POST);

$errors = [];
if (mb_strlen($title) < 3) $errors[] = 'SözFişi başlığı en az 3 karakter olmalı.';
if (mb_strlen($guestName) < 2) $errors[] = 'Karşı tarafın adını yazın.';
if ($rawText === '' && $explicit['description'] === '') $errors[] = 'Konuştuğunuz anlaşmayı veya yapılacak işi yazın.';
if ($errors !== []) {
    foreach ($errors as $error) flash('danger', $error);
    redirect(page_url('create'));
}

$analysis = AgreementAnalyzer::analyze($type, $rawText, $explicit);
if ($analysis['risk_level'] === 'red') {
    flash('danger', 'Bu işlem resmî şekil veya profesyonel hukuk desteği gerektirebilir. SözFişi bu işlem için oluşturulmadı.');
    redirect(page_url('create'));
}

$pdo = db();
$pdo->beginTransaction();
try {
    $token = random_token(28);
    $code = public_code();
    $stmt = $pdo->prepare("INSERT INTO agreements (public_code, creator_id, title, type, status, currency, total_amount, start_date, end_date, risk_level, invite_token, invite_expires_at, created_at, updated_at) VALUES (?, ?, ?, ?, 'draft', 'TRY', ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY), NOW(), NOW())");
    $stmt->execute([
        $code, (int) user()['id'], $title, $type,
        $totalAmount !== '' ? (float) $totalAmount : null,
        $explicit['start_date'] !== '' ? $explicit['start_date'] : null,
        $explicit['end_date'] !== '' ? $explicit['end_date'] : null,
        $analysis['risk_level'], $token,
    ]);
    $agreementId = (int) $pdo->lastInsertId();

    $partyStmt = $pdo->prepare("INSERT INTO agreement_parties (agreement_id, user_id, display_name, role, contact_type, contact_value, verification_level, invite_status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $partyStmt->execute([$agreementId, (int) user()['id'], user()['name'], 'creator', 'email', user()['email'], 'account', 'accepted']);
    $contactType = filter_var($guestContact, FILTER_VALIDATE_EMAIL) ? 'email' : ($guestContact !== '' ? 'phone' : 'none');
    $partyStmt->execute([$agreementId, null, $guestName, 'guest', $contactType, $guestContact !== '' ? $guestContact : null, 'none', 'pending']);

    $analysis['title'] = $title;
    $analysis['type'] = $type;
    $analysis['guest_name'] = $guestName;
    $version = create_version($agreementId, $analysis, (int) user()['id'], user()['name']);

    $approvalStmt = $pdo->prepare("INSERT INTO approvals (agreement_id, version_id, party_id, approval_type, method, approved_at, ip_hash, user_agent_hash) SELECT ?, ?, id, 'creator', 'session', NOW(), ?, ? FROM agreement_parties WHERE agreement_id = ? AND role = 'creator' LIMIT 1");
    $approvalStmt->execute([$agreementId, $version['id'], request_ip_hash(), request_agent_hash(), $agreementId]);

    log_event($agreementId, 'agreement_created', (int) user()['id'], user()['name'], [], ['version' => 1, 'risk' => $analysis['risk_level']]);
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
}

clear_old();
flash('success', 'Taslağınız oluşturuldu. Eksik kalanları kontrol edip karşı tarafa gönderebilirsiniz.');
redirect(page_url('agreement', ['id' => $agreementId]));
