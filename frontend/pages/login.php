<?php require_guest(); ?>
<section class="auth-section">
    <div class="auth-card">
        <div class="auth-logo"><img src="<?= e(url('frontend/assets/images/logo-icon.png')) ?>" alt=""></div>
        <span class="eyebrow">Tekrar hoş geldiniz</span>
        <h1>Hesabınıza giriş yapın</h1>
        <p>SözFişlerinizi, davetleri ve işlem geçmişini tek yerden takip edin.</p>
        <form method="post" action="<?= e(url('backend/actions/auth/login.php')) ?>" class="form-stack">
            <?= csrf_field() ?>
            <label>E-posta adresi<input type="email" name="email" value="<?= old('email') ?>" autocomplete="email" required></label>
            <label>Şifre<input type="password" name="password" autocomplete="current-password" required></label>
            <button class="btn btn-primary btn-block" type="submit">Giriş yap</button>
        </form>
        <p class="auth-switch">Hesabınız yok mu? <a href="<?= e(page_url('register')) ?>">Ücretsiz hesap oluşturun</a></p>
    </div>
</section>
