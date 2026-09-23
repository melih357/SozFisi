<?php
$token = trim((string) ($_GET['token'] ?? ''));
$agreement = $token !== '' ? find_agreement_by_token($token) : null;
if (!$agreement) {
    http_response_code(404);
    echo '<section class="auth-section"><div class="auth-card"><h1>Davet bulunamadı</h1><p>Bağlantı geçersiz, değiştirilmiş veya süresi dolmuş olabilir.</p><a class="btn btn-primary" href="' . e(page_url('home')) . '">Ana sayfaya dön</a></div></section>';
    return;
}
$id = (int) $agreement['id'];
$version = current_version($id);
$clauses = $version ? version_clauses((int) $version['id']) : [];
$guest = guest_party($id);
$content = json_decode_array($version['content_json'] ?? null);

if (in_array($agreement['status'], ['sent', 'draft'], true)) {
    db()->prepare("UPDATE agreements SET status = 'viewed', updated_at = NOW() WHERE id = ? AND status IN ('sent','draft')")->execute([$id]);
    if ($guest) db()->prepare("UPDATE agreement_parties SET invite_status = 'viewed' WHERE id = ?")->execute([(int) $guest['id']]);
    log_event($id, 'invite_viewed', null, $guest['display_name'] ?? 'Karşı taraf');
    $agreement['status'] = 'viewed';
}

$stmt = db()->prepare("SELECT * FROM change_requests WHERE agreement_id = ? ORDER BY id DESC");
$stmt->execute([$id]);
$changes = $stmt->fetchAll();
$stmt = db()->prepare("SELECT * FROM addendums WHERE agreement_id = ? ORDER BY id DESC");
$stmt->execute([$id]);
$addendums = $stmt->fetchAll();
$stmt = db()->prepare("SELECT * FROM completions WHERE agreement_id = ? AND status = 'pending' ORDER BY id DESC LIMIT 1");
$stmt->execute([$id]);
$completion = $stmt->fetch() ?: null;
$stmt = db()->prepare("SELECT * FROM approvals WHERE agreement_id = ? AND version_id = ? AND approval_type = 'guest' ORDER BY id DESC LIMIT 1");
$stmt->execute([$id, (int) ($version['id'] ?? 0)]);
$guestApproval = $stmt->fetch() ?: null;
?>
<section class="invite-section">
    <div class="container invite-container">
        <div class="invite-brand"><img src="<?= e(url('frontend/assets/images/logo-horizontal.png')) ?>" alt="SözFişi"><span>Güvenli paylaşım bağlantısı</span></div>
        <div class="invite-intro">
            <span class="badge badge-<?= e(status_class($agreement['status'])) ?>"><?= e(status_label($agreement['status'])) ?></span>
            <h1><?= e($agreement['title']) ?></h1>
            <p><b><?= e($agreement['creator_name']) ?></b> sizinle bir SözFişi paylaştı. Aşağıdaki son sürümü kontrol edin.</p>
            <small><?= e($agreement['public_code']) ?> · Sürüm <?= (int) ($version['version_number'] ?? 0) ?> · <?= e(format_date($version['created_at'] ?? null, true)) ?></small>
        </div>

        <?php if ($guestApproval): ?>
            <div class="completion-success"><span>✓</span><div><h2>Bu sürümü onayladınız</h2><p>Onay zamanı: <?= e(format_date($guestApproval['approved_at'], true)) ?> · Yöntem: OTP</p></div></div>
        <?php endif; ?>

        <section class="invite-card">
            <div class="invite-card-head"><div><span class="eyebrow">Ortak metin</span><h2>Anlaşma ayrıntıları</h2></div><span class="hash-pill"># <?= e(substr((string) ($version['hash'] ?? ''), 0, 12)) ?>…</span></div>
            <div class="clause-grid">
                <?php foreach ($clauses as $clause): ?><article class="clause-card"><span><?= e($clause['title']) ?></span><p><?= nl2br(e($clause['content'])) ?></p></article><?php endforeach; ?>
            </div>
            <div class="legal-note">Bu kayıt güvenli elektronik imza veya noter işlemi değildir. Tarafların aynı metni gördüğüne ilişkin işlem geçmişi oluşturur.</div>
        </section>

        <?php $pendingChange = array_filter($changes, fn($c) => $c['status'] === 'pending'); ?>
        <?php if ($pendingChange !== []): ?>
            <div class="alert alert-warning"><span>Gönderdiğiniz değişiklik talebi oluşturucunun yanıtını bekliyor.</span></div>
        <?php endif; ?>

        <?php if (!$guestApproval && !in_array($agreement['status'], ['completed', 'archived', 'cancelled'], true) && $pendingChange === []): ?>
        <section class="decision-card">
            <h2>Bu metin konuştuğunuz anlaşmayı doğru anlatıyor mu?</h2>
            <p>Kabul edebilir, belirli bir madde için değişiklik isteyebilir veya bu davetin size ait olmadığını bildirebilirsiniz.</p>
            <div class="decision-actions">
                <form method="post" action="<?= e(url('backend/actions/invitations/respond.php')) ?>">
                    <?= csrf_field() ?><input type="hidden" name="token" value="<?= e($token) ?>"><input type="hidden" name="decision" value="accept">
                    <button class="btn btn-primary btn-lg" type="submit">Metni okudum ve onaylıyorum</button>
                </form>
                <button class="btn btn-secondary btn-lg" type="button" data-toggle-target="#change-form">Değişiklik istiyorum</button>
            </div>
            <form id="change-form" method="post" action="<?= e(url('backend/actions/invitations/respond.php')) ?>" class="form-stack hidden-panel">
                <?= csrf_field() ?><input type="hidden" name="token" value="<?= e($token) ?>"><input type="hidden" name="decision" value="change">
                <label>Hangi madde?<select name="clause_key" required><option value="">Seçin</option><?php foreach ($clauses as $clause): ?><option value="<?= e($clause['clause_key']) ?>"><?= e($clause['title']) ?></option><?php endforeach; ?></select></label>
                <label>Yeni öneriniz<textarea name="requested_content" rows="4" placeholder="Örn. Teslim tarihi 8 Ağustos değil, 10 Ağustos olmalı." required></textarea></label>
                <label>Not <small>İsteğe bağlı</small><textarea name="note" rows="2"></textarea></label>
                <button class="btn btn-primary" type="submit">Değişiklik talebini gönder</button>
            </form>
            <form method="post" action="<?= e(url('backend/actions/invitations/respond.php')) ?>" class="not-mine-form" onsubmit="return confirm('Bu davetin size ait olmadığını bildirmek istiyor musunuz?')">
                <?= csrf_field() ?><input type="hidden" name="token" value="<?= e($token) ?>"><input type="hidden" name="decision" value="not_mine">
                <button class="link-button danger-link" type="submit">Bu anlaşma bana ait değil</button>
            </form>
        </section>
        <?php endif; ?>

        <?php if ($addendums !== []): ?>
        <section class="invite-card">
            <div class="invite-card-head"><div><span class="eyebrow">Ek kayıtlar</span><h2>Ek SözFişleri</h2></div></div>
            <div class="addendum-list">
                <?php foreach ($addendums as $addendum): ?>
                <article>
                    <div><b><?= e($addendum['title']) ?></b><span class="badge badge-<?= $addendum['status'] === 'approved' ? 'success' : 'warning' ?>"><?= $addendum['status'] === 'approved' ? 'Onaylandı' : 'Onay bekliyor' ?></span></div>
                    <p><?= e($addendum['reason']) ?></p>
                    <small><?= e(money($addendum['additional_amount'])) ?> · Yeni teslim: <?= e(format_date($addendum['new_end_date'])) ?></small>
                    <?php if ($addendum['status'] === 'pending'): ?>
                    <form method="post" action="<?= e(url('backend/actions/invitations/respond.php')) ?>">
                        <?= csrf_field() ?><input type="hidden" name="token" value="<?= e($token) ?>"><input type="hidden" name="decision" value="approve_addendum"><input type="hidden" name="addendum_id" value="<?= (int) $addendum['id'] ?>">
                        <button class="btn btn-secondary btn-sm" type="submit">Ek fişi OTP ile onayla</button>
                    </form>
                    <?php endif; ?>
                </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($completion): ?>
        <section class="decision-card completion-decision">
            <span class="eyebrow">Kapanış fişi</span><h2>Oluşturucu işin tamamlandığını bildirdi</h2><p><?= e($completion['request_note']) ?></p>
            <form method="post" action="<?= e(url('backend/actions/invitations/completion_response.php')) ?>" class="form-stack">
                <?= csrf_field() ?><input type="hidden" name="token" value="<?= e($token) ?>">
                <div class="completion-options">
                    <label><input type="radio" name="status" value="completed" required><span>✓ Tamamlandı</span></label>
                    <label><input type="radio" name="status" value="partial" required><span>◐ Kısmen tamamlandı</span></label>
                    <label><input type="radio" name="status" value="problem" required><span>! Sorun var</span></label>
                </div>
                <label>Not <small>İsteğe bağlı</small><textarea name="note" rows="3"></textarea></label>
                <button class="btn btn-primary" type="submit">Kapanış yanıtını gönder</button>
            </form>
        </section>
        <?php endif; ?>

        <div class="invite-footer-note">SözFişi, iki tarafın yerine karar vermez; iki tarafın aynı şeyi gördüğünden emin olur.</div>
    </div>
</section>
