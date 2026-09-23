<?php
require_admin();
$counts = [];
foreach (['users', 'agreements', 'reports', 'attachments'] as $table) {
    $counts[$table] = (int) db()->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
}
$reports = db()->query("SELECT r.*, a.public_code, a.title AS agreement_title FROM reports r LEFT JOIN agreements a ON a.id = r.agreement_id ORDER BY r.id DESC LIMIT 50")->fetchAll();
?>
<section class="dashboard-section">
    <div class="container">
        <div class="page-head"><div><span class="eyebrow">Yönetim</span><h1>Sistem özeti</h1><p>Kötüye kullanım bildirimleri ve temel MVP metrikleri.</p></div><a class="btn btn-secondary" href="<?= e(url('backend/tools/health.php')) ?>" target="_blank">Sistem sağlığı</a></div>
        <div class="stats-grid"><article><span>Kullanıcı</span><strong><?= $counts['users'] ?></strong></article><article><span>SözFişi</span><strong><?= $counts['agreements'] ?></strong></article><article><span>Bildirim</span><strong><?= $counts['reports'] ?></strong></article><article><span>Dosya</span><strong><?= $counts['attachments'] ?></strong></article></div>
        <section class="panel">
            <div class="panel-head"><div><span class="eyebrow">Güven kuyruğu</span><h2>Bildirimler</h2></div></div>
            <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Tarih</th><th>SözFişi</th><th>Tür</th><th>Açıklama</th><th>Durum</th><th>İşlem</th></tr></thead><tbody>
            <?php foreach ($reports as $report): ?><tr><td><?= e(format_date($report['created_at'], true)) ?></td><td><?= e($report['public_code'] ?? '-') ?><br><small><?= e($report['agreement_title'] ?? '') ?></small></td><td><?= e($report['type']) ?></td><td><?= e($report['description']) ?></td><td><span class="badge badge-<?= $report['status'] === 'closed' ? 'success' : 'warning' ?>"><?= e($report['status']) ?></span></td><td><form method="post" action="<?= e(url('backend/actions/admin/report_update.php')) ?>"><?= csrf_field() ?><input type="hidden" name="report_id" value="<?= (int) $report['id'] ?>"><select name="status"><option value="open">Açık</option><option value="reviewing">İnceleniyor</option><option value="closed">Kapalı</option></select><button class="btn btn-secondary btn-sm" type="submit">Kaydet</button></form></td></tr><?php endforeach; ?>
            <?php if ($reports === []): ?><tr><td colspan="6">Bildirim bulunmuyor.</td></tr><?php endif; ?>
            </tbody></table></div>
        </section>
    </div>
</section>
