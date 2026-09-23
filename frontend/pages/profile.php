<?php require_auth(); ?>
<section class="dashboard-section">
    <div class="container narrow-container">
        <div class="page-head"><div><span class="eyebrow">Hesap ayarları</span><h1>Profil</h1><p>Karşı tarafa görünen temel bilgilerinizi yönetin.</p></div></div>
        <div class="profile-grid">
            <section class="panel">
                <div class="profile-avatar"><?= e(mb_strtoupper(mb_substr(user()['name'], 0, 1))) ?></div>
                <form method="post" action="<?= e(url('backend/actions/profile/update.php')) ?>" class="form-stack">
                    <?= csrf_field() ?>
                    <label>Ad soyad<input type="text" name="name" value="<?= e(user()['name']) ?>" required></label>
                    <label>E-posta<input type="email" value="<?= e(user()['email']) ?>" disabled><small>E-posta değiştirme ilk sürümde kapalıdır.</small></label>
                    <label>Telefon<input type="tel" name="phone" value="<?= e(user()['phone'] ?? '') ?>"></label>
                    <button class="btn btn-primary" type="submit">Bilgileri kaydet</button>
                </form>
            </section>
            <section class="side-card plan-card">
                <span class="eyebrow">Planınız</span><h2><?= e(ucfirst(user()['plan'])) ?></h2><p>Yerel MVP sürümünde plan sınırları bilgilendirme amaçlıdır.</p>
                <ul class="check-list"><li>Temel şablonlar</li><li>Bağlantıyla onay</li><li>Yerel OTP testi</li><li>Yazdırma / PDF görünümü</li></ul>
            </section>
        </div>
    </div>
</section>
