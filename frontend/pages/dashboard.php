<?php
require_auth();
$uid = (int) user()['id'];
$stmt = db()->prepare("SELECT a.*,
    (SELECT display_name FROM agreement_parties p WHERE p.agreement_id = a.id AND p.role = 'guest' LIMIT 1) AS guest_name,
    (SELECT COUNT(*) FROM attachments at WHERE at.agreement_id = a.id) AS attachment_count
    FROM agreements a WHERE a.creator_id = ? ORDER BY a.updated_at DESC");
$stmt->execute([$uid]);
$agreements = $stmt->fetchAll();

$stats = ['total' => count($agreements), 'active' => 0, 'waiting' => 0, 'completed' => 0];
foreach ($agreements as $a) {
    if (in_array($a['status'], ['approved', 'active'], true)) $stats['active']++;
    if (in_array($a['status'], ['sent', 'viewed', 'changes_requested', 'completion_pending'], true)) $stats['waiting']++;
    if ($a['status'] === 'completed') $stats['completed']++;
}
?>
<section class="dashboard-section">
    <div class="container">
        <div class="page-head">
            <div><span class="eyebrow">Merhaba, <?= e(explode(' ', user()['name'])[0]) ?></span><h1>SözFişlerim</h1><p>Konuşulanları, onayları ve tamamlanma durumunu tek ekrandan takip edin.</p></div>
            <a class="btn btn-primary" href="<?= e(page_url('create')) ?>">+ Yeni SözFişi</a>
        </div>

        <div class="stats-grid">
            <article><span>Toplam kayıt</span><strong><?= $stats['total'] ?></strong><small>Tüm zamanlar</small></article>
            <article><span>Aktif</span><strong><?= $stats['active'] ?></strong><small>Devam eden işler</small></article>
            <article><span>Yanıt bekleyen</span><strong><?= $stats['waiting'] ?></strong><small>Karşı taraf veya kapanış</small></article>
            <article><span>Tamamlanan</span><strong><?= $stats['completed'] ?></strong><small>Kapanış kaydı oluşan</small></article>
        </div>

        <div class="list-toolbar">
            <div>
                <button class="filter-btn active" data-filter="all">Tümü</button>
                <button class="filter-btn" data-filter="draft">Taslak</button>
                <button class="filter-btn" data-filter="active">Aktif</button>
                <button class="filter-btn" data-filter="completed">Tamamlanan</button>
            </div>
            <label class="search-box">⌕<input type="search" placeholder="Başlık veya kişi ara" data-agreement-search></label>
        </div>

        <?php if ($agreements === []): ?>
            <div class="empty-state">
                <img src="<?= e(url('frontend/assets/images/logo-icon.png')) ?>" alt="">
                <h2>Henüz bir SözFişi yok</h2>
                <p>İlk anlaşmanızı birkaç adımda ortak kayda dönüştürün.</p>
                <a class="btn btn-primary" href="<?= e(page_url('create')) ?>">İlk SözFişi’ni oluştur</a>
            </div>
        <?php else: ?>
            <div class="agreement-list" data-agreement-list>
                <?php foreach ($agreements as $agreement): ?>
                    <a class="agreement-row" href="<?= e(page_url('agreement', ['id' => $agreement['id']])) ?>" data-status="<?= e($agreement['status']) ?>" data-search="<?= e(mb_strtolower($agreement['title'] . ' ' . ($agreement['guest_name'] ?? ''))) ?>">
                        <div class="agreement-code"><span><?= e(mb_substr(type_label($agreement['type']), 0, 1)) ?></span></div>
                        <div class="agreement-main">
                            <div><h3><?= e($agreement['title']) ?></h3><span class="badge badge-<?= e(status_class($agreement['status'])) ?>"><?= e(status_label($agreement['status'])) ?></span></div>
                            <p><?= e(type_label($agreement['type'])) ?> · <?= e($agreement['guest_name'] ?? 'Karşı taraf') ?></p>
                            <small><?= e($agreement['public_code']) ?> · Son güncelleme <?= e(format_date($agreement['updated_at'], true)) ?></small>
                        </div>
                        <div class="agreement-meta">
                            <strong><?= e(money($agreement['total_amount'], $agreement['currency'])) ?></strong>
                            <span><?= (int) $agreement['attachment_count'] ?> dosya</span>
                            <b>→</b>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
