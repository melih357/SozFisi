<?php require_guest(); ?>
<section class="auth-section">
    <div class="auth-card auth-card-wide">
        <div class="auth-logo"><img src="<?= e(url('frontend/assets/images/logo-icon.png')) ?>" alt=""></div>
        <span class="eyebrow">İlk SözFişi ücretsiz</span>
        <h1>Hesabınızı oluşturun</h1>
        <p>Karşı tarafın hesap açması gerekmez. Siz oluşturun, bağlantıyı paylaşın.</p>
        <form method="post" action="<?= e(url('backend/actions/auth/register.php')) ?>" class="form-stack">
            <?= csrf_field() ?>
            <div class="form-grid two">
                <label>Ad soyad<input type="text" name="name" value="<?= old('name') ?>" autocomplete="name" required></label>
                <label>Telefon <small>İsteğe bağlı</small><input type="tel" name="phone" value="<?= old('phone') ?>" autocomplete="tel"></label>
            </div>
            <label>E-posta adresi<input type="email" name="email" value="<?= old('email') ?>" autocomplete="email" required></label>
            <div class="form-grid two">
                <label>Şifre<input type="password" name="password" minlength="8" autocomplete="new-password" required></label>
                <label>Şifre tekrar<input type="password" name="password_confirmation" minlength="8" autocomplete="new-password" required></label>
            </div>
            <label class="check-row"><input type="checkbox" required><span>Kullanım koşullarını ve gizlilik açıklamasını okudum.</span></label>
            <button class="btn btn-primary btn-block" type="submit">Ücretsiz hesap oluştur</button>
        </form>
        <p class="auth-switch">Zaten hesabınız var mı? <a href="<?= e(page_url('login')) ?>">Giriş yapın</a></p>
    </div>
</section>
