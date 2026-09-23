<?php require_auth(); ?>
<section class="create-section">
    <div class="container narrow-container">
        <div class="page-head centered">
            <div><span class="eyebrow">Anlat → Netleştir → Birlikte onayla</span><h1>Yeni SözFişi oluştur</h1><p>Önce durumu seçin, sonra konuştuğunuzu gündelik dille anlatın.</p></div>
        </div>

        <form method="post" action="<?= e(url('backend/actions/agreements/create.php')) ?>" class="wizard-form" data-create-form>
            <?= csrf_field() ?>
            <section class="wizard-card">
                <div class="wizard-head"><span>1</span><div><h2>Ne için oluşturuyorsunuz?</h2><p>Eksik koşul önerileri seçtiğiniz duruma göre değişir.</p></div></div>
                <div class="template-grid" data-template-grid>
                    <?php
                    $types = [
                        'service' => ['🎨', 'Müşteriye iş yapıyorum', 'Freelance, tasarım, video, danışmanlık'],
                        'purchase_service' => ['🛠️', 'Bir iş yaptırıyorum', 'Usta, tadilat, bakım, teknik servis'],
                        'sale' => ['📱', 'Bir şey satıyorum', 'İkinci el ürün ve teslim kaydı'],
                        'debt' => ['💸', 'Birine borç veriyorum', 'Tutar ve geri ödeme tarihi'],
                        'loan_item' => ['🔧', 'Eşya ödünç veriyorum', 'Durum, aksesuar ve iade tarihi'],
                        'shared_expense' => ['🏠', 'Ortak masraf yapıyoruz', 'Kişi başı pay ve ödeme zamanı'],
                        'deposit' => ['📅', 'Kapora veriliyor', 'Rezervasyon, tarih ve iptal şartı'],
                        'other' => ['✦', 'Başka bir anlaşma', 'Kendi koşullarınızı yazın'],
                    ];
                    foreach ($types as $value => [$icon, $label, $desc]): ?>
                        <label class="template-card">
                            <input type="radio" name="type" value="<?= e($value) ?>" <?= old('type', 'service') === $value ? 'checked' : '' ?> required>
                            <span class="template-icon"><?= $icon ?></span><b><?= e($label) ?></b><small><?= e($desc) ?></small>
                        </label>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="wizard-card">
                <div class="wizard-head"><span>2</span><div><h2>Konuştuğunuzu anlatın</h2><p>Mesajın ilgili bölümünü yapıştırabilir veya mikrofonla anlatabilirsiniz.</p></div></div>
                <div class="form-stack">
                    <label>SözFişi başlığı<input type="text" name="title" value="<?= old('title') ?>" placeholder="Örn. Logo ve kartvizit tasarımı" maxlength="120" required></label>
                    <div class="form-grid two">
                        <label>Karşı tarafın adı<input type="text" name="guest_name" value="<?= old('guest_name') ?>" placeholder="Örn. Mert Yılmaz" required></label>
                        <label>Telefon veya e-posta <small>İsteğe bağlı</small><input type="text" name="guest_contact" value="<?= old('guest_contact') ?>" placeholder="05xx... veya e-posta"></label>
                    </div>
                    <label class="textarea-label">Ne konuşuldu?
                        <textarea name="raw_text" rows="7" placeholder="Örn. Müşteriye üç sosyal medya videosu hazırlayacağım. Toplam 12.000 TL. Yarısı başta, yarısı teslimde ödenecek. Bir hafta içinde teslim edeceğim." data-speech-target><?= old('raw_text') ?></textarea>
                    </label>
                    <div class="input-actions">
                        <button class="btn btn-secondary btn-sm" type="button" data-speech-button>🎙️ Konuşarak anlat</button>
                        <span class="muted small" data-speech-status>Tarayıcınız destekliyorsa Türkçe sesli giriş açılır.</span>
                    </div>
                    <div class="privacy-hint">🔒 Başka kişilere ait mesajları eklemeden önce kişisel bilgileri kontrol edin.</div>
                </div>
            </section>

            <section class="wizard-card">
                <div class="wizard-head"><span>3</span><div><h2>Net ayrıntılar</h2><p>Bildiklerinizi yazın. Konuşmadığınız alanları boş bırakabilirsiniz.</p></div></div>
                <div class="form-stack">
                    <label>Yapılacak iş veya işlem<textarea name="description" rows="3" placeholder="Teslim edilecek işi veya ürünün durumunu net biçimde yazın."><?= old('description') ?></textarea></label>
                    <div class="form-grid three">
                        <label>Toplam tutar<input type="text" inputmode="decimal" name="total_amount" value="<?= old('total_amount') ?>" placeholder="12000"></label>
                        <label>Başlangıç tarihi<input type="date" name="start_date" value="<?= old('start_date') ?>"></label>
                        <label>Teslim / bitiş tarihi<input type="date" name="end_date" value="<?= old('end_date') ?>"></label>
                    </div>
                    <label>Ödeme planı<textarea name="payment_terms" rows="2" placeholder="Örn. 6.000 TL başlangıçta, 6.000 TL teslimde."><?= old('payment_terms') ?></textarea></label>
                    <div class="form-grid two">
                        <label>Fiyata dahil olanlar<textarea name="included_items" rows="3" placeholder="Örn. 3 taslak ve 2 revizyon"><?= old('included_items') ?></textarea></label>
                        <label>Fiyata dahil olmayanlar<textarea name="excluded_items" rows="3" placeholder="Örn. Baskı ve çekim hizmeti"><?= old('excluded_items') ?></textarea></label>
                    </div>
                    <div class="form-grid two">
                        <label>Revizyon / değişiklik hakkı<textarea name="revision_terms" rows="3" placeholder="Örn. İki revizyon dahildir."><?= old('revision_terms') ?></textarea></label>
                        <label>İptal veya gecikme durumu<textarea name="cancellation_terms" rows="3" placeholder="Örn. İçerik geç gelirse teslim aynı süre uzar."><?= old('cancellation_terms') ?></textarea></label>
                    </div>
                    <label>Diğer notlar<textarea name="notes" rows="3" placeholder="Dosya türü, garanti, teslim yeri gibi diğer ayrıntılar."><?= old('notes') ?></textarea></label>
                </div>
            </section>

            <div class="wizard-submit">
                <div><b>Yerel analiz motoru</b><span>Eksik koşulları ve belirsiz ifadeleri işaretler. Taraflar adına şart üretmez.</span></div>
                <button class="btn btn-primary btn-lg" type="submit">SözFişi taslağını oluştur →</button>
            </div>
        </form>
    </div>
</section>
