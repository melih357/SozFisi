<?php
$token = trim((string) ($_GET['token'] ?? ''));
$pending = $_SESSION['pending_otp'] ?? null;
if (!is_array($pending) || ($pending['token'] ?? '') !== $token) {
    echo '<section class="auth-section"><div class="auth-card"><h1>Doğrulama isteği bulunamadı</h1><p>Önce SözFişi bağlantısından onay işlemini başlatın.</p><a class="btn btn-primary" href="' . e(page_url('invite', ['token' => $token])) . '">Davet sayfasına dön</a></div></section>';
    return;
}
$agreement = find_agreement_by_token($token);
?>
<section class="auth-section">
    <div class="auth-card otp-card">
        <div class="auth-logo"><img src="<?= e(url('frontend/assets/images/logo-icon.png')) ?>" alt=""></div>
        <span class="eyebrow">Tek kullanımlık doğrulama</span>
        <h1>6 haneli kodu girin</h1>
        <p><?= e($agreement['title'] ?? 'SözFişi') ?> kaydını onaylamak için oluşturulan kod 5 dakika geçerlidir.</p>
        <?php if ((bool) env('OTP_DEBUG', false) && !empty($_SESSION['debug_otp'])): ?>
            <div class="debug-code"><span>Yerel test kodu</span><strong><?= e($_SESSION['debug_otp']) ?></strong><small>Canlı ortamda bu kod ekranda gösterilmez; SMS/e-posta sağlayıcısı üzerinden gönderilir.</small></div>
        <?php endif; ?>
        <form method="post" action="<?= e(url('backend/actions/invitations/verify_otp.php')) ?>" class="form-stack">
            <?= csrf_field() ?>
            <label>Doğrulama kodu<input class="otp-input" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" name="code" autocomplete="one-time-code" placeholder="000000" required autofocus></label>
            <button class="btn btn-primary btn-block" type="submit">Kodu doğrula ve onayla</button>
        </form>
        <p class="muted small">Onay zamanı, sürüm, cihaz özeti ve doğrulama yöntemi işlem geçmişine kaydedilir.</p>
    </div>
</section>
