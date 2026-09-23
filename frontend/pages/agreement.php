<?php
require_auth();
$id = (int) ($_GET['id'] ?? 0);
$agreement = find_agreement_for_user($id, (int) user()['id']);
if (!$agreement) {
    http_response_code(404);
    echo '<section class="section"><div class="container"><div class="empty-state"><h1>SözFişi bulunamadı</h1><a class="btn btn-primary" href="' . e(page_url('dashboard')) . '">SözFişlerime dön</a></div></div></section>';
    return;
}
$version = current_version($id);
$clauses = $version ? version_clauses((int) $version['id']) : [];
$parties = agreement_parties($id);
$guest = guest_party($id);
$content = json_decode_array($version['content_json'] ?? null);
$missing = $content['missing'] ?? [];
$ambiguities = $content['ambiguities'] ?? [];

$stmt = db()->prepare('SELECT * FROM event_logs WHERE agreement_id = ? ORDER BY id DESC LIMIT 50');
$stmt->execute([$id]);
$events = $stmt->fetchAll();
$stmt = db()->prepare('SELECT * FROM attachments WHERE agreement_id = ? ORDER BY id DESC');
$stmt->execute([$id]);
$attachments = $stmt->fetchAll();
$stmt = db()->prepare("SELECT cr.*, p.display_name FROM change_requests cr LEFT JOIN agreement_parties p ON p.id = cr.party_id WHERE cr.agreement_id = ? ORDER BY cr.id DESC");
$stmt->execute([$id]);
$changes = $stmt->fetchAll();
$stmt = db()->prepare('SELECT * FROM addendums WHERE agreement_id = ? ORDER BY id DESC');
$stmt->execute([$id]);
$addendums = $stmt->fetchAll();
$stmt = db()->prepare('SELECT * FROM completions WHERE agreement_id = ? ORDER BY id DESC LIMIT 1');
$stmt->execute([$id]);
$completion = $stmt->fetch() ?: null;
$shareUrl = agreement_share_url($agreement);
?>
<section class="agreement-detail-section">
    <div class="container">
        <div class="agreement-header">
            <div>
                <a class="back-link" href="<?= e(page_url('dashboard')) ?>">← SözFişlerim</a>
                <div class="title-row"><h1><?= e($agreement['title']) ?></h1><span class="badge badge-<?= e(status_class($agreement['status'])) ?>"><?= e(status_label($agreement['status'])) ?></span></div>
                <p><?= e($agreement['public_code']) ?> · <?= e(type_label($agreement['type'])) ?> · Sürüm <?= (int) ($version['version_number'] ?? 0) ?></p>
            </div>
            <div class="header-actions">
                <a class="btn btn-secondary" href="<?= e(page_url('agreement', ['id' => $id, 'print' => 1])) ?>" target="_blank">Yazdır / PDF</a>
                <?php if ($agreement['status'] === 'draft'): ?>
                    <form method="post" action="<?= e(url('backend/actions/agreements/send.php')) ?>">
                        <?= csrf_field() ?><input type="hidden" name="agreement_id" value="<?= $id ?>">
                        <button class="btn btn-primary" type="submit">Karşı tarafa gönder</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($agreement['status'] !== 'draft'): ?>
        <div class="share-bar">
            <div><span>Paylaşım bağlantısı</span><code data-share-url><?= e($shareUrl) ?></code></div>
            <button class="btn btn-primary btn-sm" type="button" data-copy="<?= e($shareUrl) ?>">Bağlantıyı kopyala</button>
        </div>
        <?php endif; ?>

        <?php if ($agreement['risk_level'] === 'yellow'): ?>
            <div class="alert alert-warning"><span>Bu kayıt borç, kapora veya yüksek tutar nedeniyle ek dikkat gerektirebilir. SözFişi hukuk danışmanlığı veya güvenli elektronik imza değildir.</span></div>
        <?php endif; ?>

        <div class="detail-layout">
            <div class="detail-main">
                <section class="panel">
                    <div class="panel-head"><div><span class="eyebrow">Ortak metin</span><h2>Anlaşma kartları</h2></div><span class="hash-pill" title="SHA-256"># <?= e(substr((string) ($version['hash'] ?? ''), 0, 12)) ?>…</span></div>
                    <div class="clause-grid">
                        <?php foreach ($clauses as $clause): ?>
                            <article class="clause-card"><span><?= e($clause['title']) ?></span><p><?= nl2br(e($clause['content'])) ?></p></article>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($clauses === []): ?><p class="muted">Henüz madde bulunmuyor.</p><?php endif; ?>
                    <div class="legal-note">SözFişi tarafların söylediği şartları düzenler; taraflar adına yeni şart oluşturmaz ve noter işleminin yerine geçmez.</div>
                </section>

                <?php if ($missing !== [] || $ambiguities !== []): ?>
                <section class="panel radar-panel">
                    <div class="panel-head"><div><span class="eyebrow">Eksik koşul radarı</span><h2>Kontrol edilmesi gerekenler</h2></div><span class="ai-pill">Yerel analiz</span></div>
                    <?php foreach ($missing as $i => $item): ?><div class="radar-line"><span><?= $i + 1 ?></span><p><?= e($item) ?></p></div><?php endforeach; ?>
                    <?php foreach ($ambiguities as $item): ?><div class="radar-line ambiguity"><span>!</span><p><?= e($item) ?></p></div><?php endforeach; ?>
                    <p class="muted small">Öneri — taraflar tarafından kontrol edilmelidir.</p>
                </section>
                <?php endif; ?>

                <?php $pendingChanges = array_filter($changes, fn($c) => $c['status'] === 'pending'); ?>
                <?php if ($pendingChanges !== []): ?>
                <section class="panel">
                    <div class="panel-head"><div><span class="eyebrow">Karşı teklif</span><h2>Değişiklik talepleri</h2></div></div>
                    <?php foreach ($pendingChanges as $change): ?>
                        <article class="change-card">
                            <div><span>Eski</span><p><?= e($change['old_content']) ?></p></div>
                            <div><span>Yeni teklif</span><p><?= e($change['requested_content']) ?></p></div>
                            <?php if ($change['note']): ?><small>Not: <?= e($change['note']) ?></small><?php endif; ?>
                            <div class="change-actions">
                                <form method="post" action="<?= e(url('backend/actions/agreements/resolve_change.php')) ?>"><?= csrf_field() ?><input type="hidden" name="agreement_id" value="<?= $id ?>"><input type="hidden" name="request_id" value="<?= (int) $change['id'] ?>"><input type="hidden" name="decision" value="reject"><button class="btn btn-secondary btn-sm" type="submit">Reddet</button></form>
                                <form method="post" action="<?= e(url('backend/actions/agreements/resolve_change.php')) ?>"><?= csrf_field() ?><input type="hidden" name="agreement_id" value="<?= $id ?>"><input type="hidden" name="request_id" value="<?= (int) $change['id'] ?>"><input type="hidden" name="decision" value="accept"><button class="btn btn-primary btn-sm" type="submit">Kabul et ve yeni sürüm oluştur</button></form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>
                <?php endif; ?>

                <section class="panel">
                    <div class="panel-head"><div><span class="eyebrow">Dosyalar</span><h2>Ekler ve kanıtlar</h2></div><span><?= count($attachments) ?> dosya</span></div>
                    <form method="post" enctype="multipart/form-data" action="<?= e(url('backend/actions/attachments/upload.php')) ?>" class="upload-row">
                        <?= csrf_field() ?><input type="hidden" name="agreement_id" value="<?= $id ?>">
                        <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf" required>
                        <button class="btn btn-secondary btn-sm" type="submit">Dosya yükle</button>
                    </form>
                    <div class="file-list">
                        <?php foreach ($attachments as $file): ?>
                            <div><span><?= str_starts_with($file['mime_type'], 'image/') ? '🖼️' : '📄' ?></span><p><b><?= e($file['original_name']) ?></b><small><?= number_format(((int) $file['size_bytes']) / 1024, 1, ',', '.') ?> KB · #<?= e(substr($file['sha256'], 0, 10)) ?>…</small></p></div>
                        <?php endforeach; ?>
                        <?php if ($attachments === []): ?><p class="muted">Henüz dosya eklenmedi.</p><?php endif; ?>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-head"><div><span class="eyebrow">Değişiklik fişi</span><h2>Ek SözFişi oluştur</h2></div></div>
                    <form method="post" action="<?= e(url('backend/actions/agreements/addendum.php')) ?>" class="form-stack compact-form">
                        <?= csrf_field() ?><input type="hidden" name="agreement_id" value="<?= $id ?>">
                        <div class="form-grid two"><label>Başlık<input type="text" name="title" value="Ek iş / değişiklik" required></label><label>Ek tutar<input type="text" name="additional_amount" placeholder="4000"></label></div>
                        <label>Değişikliğin nedeni<textarea name="reason" rows="3" placeholder="Örn. Dolapların arkasında su hasarı çıktı ve ek tamirat gerekiyor." required></textarea></label>
                        <label>Yeni teslim tarihi <small>İsteğe bağlı</small><input type="date" name="new_end_date"></label>
                        <button class="btn btn-secondary" type="submit">Ek SözFişi oluştur</button>
                    </form>
                    <?php if ($addendums !== []): ?><div class="addendum-list"><?php foreach ($addendums as $addendum): ?><article><div><b><?= e($addendum['title']) ?></b><span class="badge badge-<?= $addendum['status'] === 'approved' ? 'success' : 'warning' ?>"><?= $addendum['status'] === 'approved' ? 'Onaylandı' : 'Onay bekliyor' ?></span></div><p><?= e($addendum['reason']) ?></p><small><?= e(money($addendum['additional_amount'])) ?> · Yeni teslim: <?= e(format_date($addendum['new_end_date'])) ?></small></article><?php endforeach; ?></div><?php endif; ?>
                </section>

                <?php if (in_array($agreement['status'], ['approved', 'active'], true)): ?>
                <section class="panel completion-panel">
                    <div><span class="eyebrow">Kapanış fişi</span><h2>İş tamamlandı mı?</h2><p>Karşı taraf tamamlandı, kısmen tamamlandı veya sorun var cevabı verebilir.</p></div>
                    <form method="post" action="<?= e(url('backend/actions/agreements/request_completion.php')) ?>">
                        <?= csrf_field() ?><input type="hidden" name="agreement_id" value="<?= $id ?>"><input type="hidden" name="note" value="İş tamamlandı; kapanış onayı bekleniyor."><button class="btn btn-primary" type="submit">İşi tamamladım</button>
                    </form>
                </section>
                <?php elseif ($agreement['status'] === 'completion_pending'): ?>
                    <div class="alert alert-info"><span>Karşı tarafın tamamlama yanıtı bekleniyor.</span></div>
                <?php elseif ($agreement['status'] === 'completed'): ?>
                    <div class="completion-success"><span>✓</span><div><h2>SözFişi tamamlandı</h2><p><?= e($completion['response_note'] ?? 'Tarafların açık bir talebi kalmadı.') ?></p></div></div>
                <?php endif; ?>
            </div>

            <aside class="detail-aside">
                <section class="side-card">
                    <h3>Taraflar</h3>
                    <?php foreach ($parties as $party): ?><div class="party-row"><span><?= $party['role'] === 'creator' ? 'S' : 'K' ?></span><p><b><?= e($party['display_name']) ?></b><small><?= $party['role'] === 'creator' ? 'Oluşturan' : 'Karşı taraf' ?> · <?= e($party['verification_level']) ?></small></p></div><?php endforeach; ?>
                </section>
                <section class="side-card">
                    <h3>Özet</h3>
                    <dl class="summary-list"><div><dt>Tutar</dt><dd><?= e(money($agreement['total_amount'], $agreement['currency'])) ?></dd></div><div><dt>Başlangıç</dt><dd><?= e(format_date($agreement['start_date'])) ?></dd></div><div><dt>Teslim</dt><dd><?= e(format_date($agreement['end_date'])) ?></dd></div><div><dt>Risk</dt><dd><?= e($agreement['risk_level'] === 'green' ? 'Düşük' : ($agreement['risk_level'] === 'yellow' ? 'Dikkat' : 'Yüksek')) ?></dd></div></dl>
                </section>
                <section class="side-card timeline-card">
                    <h3>İşlem geçmişi</h3>
                    <?php foreach ($events as $event): ?><div class="timeline-item"><span></span><p><b><?= e(str_replace('_', ' ', $event['event_type'])) ?></b><small><?= e($event['actor_label'] ?: 'Sistem') ?> · <?= e(format_date($event['created_at'], true)) ?></small></p></div><?php endforeach; ?>
                </section>
                <?php if (!$printMode): ?>
                <form method="post" action="<?= e(url('backend/actions/agreements/archive.php')) ?>" onsubmit="return confirm('Bu SözFişi arşivlensin mi?')">
                    <?= csrf_field() ?><input type="hidden" name="agreement_id" value="<?= $id ?>"><button class="btn btn-ghost btn-block" type="submit">Arşivle</button>
                </form>
                <?php endif; ?>
            </aside>
        </div>
    </div>
</section>
<?php if ($printMode): ?><script>window.addEventListener('load',()=>window.print())</script><?php endif; ?>
