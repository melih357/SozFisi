<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_auth();
verify_csrf();
$id = (int) ($_POST['agreement_id'] ?? 0);
$agreement = find_agreement_for_user($id, (int) user()['id']);
if ($agreement) {
    db()->prepare("UPDATE agreements SET status = 'archived', updated_at = NOW() WHERE id = ?")->execute([$id]);
    log_event($id, 'archived', (int) user()['id'], user()['name'], ['status' => $agreement['status']], ['status' => 'archived']);
    flash('success', 'SözFişi arşivlendi.');
}
redirect(page_url('dashboard'));
