<section class="hero">
    <div class="container hero-grid">
        <div class="hero-copy">
            <span class="eyebrow">Gündelik anlaşmaların ortak hafızası</span>
            <h1>Konuştuklarınızı <span>90 saniyede</span> netleştirin.</h1>
            <p>SözFişi, iki kişinin vardığı anlaşmayı birlikte kontrol edilen, değişiklikleri görünen ve tamamlanana kadar takip edilen ortak bir kayda dönüştürür.</p>
            <div class="hero-actions">
                <a class="btn btn-primary btn-lg" href="<?= e($loggedIn ? page_url('create') : page_url('register')) ?>">Yeni SözFişi oluştur</a>
                <a class="btn btn-secondary btn-lg" href="#demo">Örnek kaydı gör</a>
            </div>
            <div class="trust-inline">
                <span>✓ Karşı taraf uygulama indirmez</span>
                <span>✓ Değişiklikler sürüm olarak saklanır</span>
                <span>✓ WAMP üzerinde çalışır</span>
            </div>
        </div>
        <div class="hero-visual" aria-label="Örnek SözFişi kartı">
            <div class="floating-chip chip-one">Eksik koşul radarı</div>
            <div class="agreement-card mock-card">
                <div class="mock-top">
                    <img src="<?= e(url('frontend/assets/images/logo-icon.png')) ?>" alt="">
                    <div>
                        <small>SF-8K2P4L</small>
                        <strong>3 sosyal medya videosu</strong>
                    </div>
                    <span class="badge badge-success">Onaylandı</span>
                </div>
                <div class="mock-clause"><span>Yapılacak iş</span><b>3 adet dikey video</b></div>
                <div class="mock-clause"><span>Toplam ücret</span><b>12.000 TL</b></div>
                <div class="mock-clause"><span>Teslim</span><b>8 Ağustos 2026</b></div>
                <div class="mock-clause"><span>Revizyon</span><b>Her video için 1</b></div>
                <div class="progress-row"><span></span><span></span><span></span><span></span></div>
                <p class="muted small">Anlat → Netleştir → Birlikte onayla → Takip et → Kapat</p>
            </div>
            <div class="floating-chip chip-two">Aynı son sürüm ✓</div>
        </div>
    </div>
</section>

<section class="section" id="nasil-calisir">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Nasıl çalışır?</span>
            <h2>Uzun sözleşmeler değil, net kartlar.</h2>
            <p>İnsanların zaten yaptığı konuşmayı bozmadan, belirsiz noktaları görünür hâle getirir.</p>
        </div>
        <div class="steps-grid">
            <article><span>01</span><h3>Anlat</h3><p>Yazarak, konuşarak veya mesajın ilgili kısmını yapıştırarak ne üzerinde anlaştığınızı söyleyin.</p></article>
            <article><span>02</span><h3>Netleştir</h3><p>Sistem eksik kalan ödeme, teslim, revizyon ve kapsam ayrıntılarını gösterir.</p></article>
            <article><span>03</span><h3>Birlikte onayla</h3><p>Karşı taraf bağlantıdan açar; kabul eder veya madde bazında değişiklik ister.</p></article>
            <article><span>04</span><h3>Takip et ve kapat</h3><p>Ek işi, dosyaları, tamamlanmayı ve kapanış kaydını aynı işlem geçmişinde tutun.</p></article>
        </div>
    </div>
</section>

<section class="section section-soft" id="kullanim">
    <div class="container">
        <div class="section-head split-head"><div><span class="eyebrow">Kullanım alanları</span><h2>Gündelik işlerin çoğunda aynı sorun var.</h2></div><p>İki kişi aynı konuşmayı farklı hatırlayabiliyor. SözFişi bunu iş büyümeden önce çözer.</p></div>
        <div class="use-grid">
            <article><div class="use-icon">🎨</div><h3>Müşteri işi</h3><p>Teslim, ücret, revizyon ve dosya kapsamını netleştirin.</p></article>
            <article><div class="use-icon">🛠️</div><h3>Usta ve tadilat</h3><p>Malzeme, işçilik, ek iş ve teslim tarihini birlikte yazın.</p></article>
            <article><div class="use-icon">📱</div><h3>İkinci el satış</h3><p>Ürünün durumunu, kusurlarını, ödeme ve teslimi kaydedin.</p></article>
            <article><div class="use-icon">💸</div><h3>Borç</h3><p>Tutarı, ödeme tarihini ve yöntemi günlük dille netleştirin.</p></article>
            <article><div class="use-icon">🔧</div><h3>Eşya ödünç</h3><p>Mevcut durumu, aksesuarları ve iade tarihini saklayın.</p></article>
            <article><div class="use-icon">🏠</div><h3>Ortak masraf</h3><p>Kimin ne kadar ve ne zaman ödeyeceğini görünür yapın.</p></article>
        </div>
    </div>
</section>

<section class="section" id="demo">
    <div class="container demo-grid">
        <div>
            <span class="eyebrow">Sözleşme dili değil</span>
            <h2>İki tarafın da anlayacağı gündelik dil.</h2>
            <p>“Müteselsil sorumluluk” gibi ağır ifadeler yerine “Fiyata neler dahil?” ve “Bir sorun çıkarsa ne olacak?” sorularını kullanır.</p>
            <ul class="check-list">
                <li>Onaylanan içerik sessizce değiştirilemez.</li>
                <li>Her değişiklik yeni sürüm olarak görünür.</li>
                <li>Telefon OTP kaydı yerel test modunda çalışır.</li>
                <li>PDF için yazdırma görünümü hazırdır.</li>
            </ul>
        </div>
        <div class="radar-card">
            <div class="radar-title"><span>✦</span><div><b>Eksik kalanlar</b><small>Logo tasarım işi</small></div></div>
            <div class="radar-item"><span>1</span><p>Kaynak dosyalar teslim edilecek mi?</p></div>
            <div class="radar-item"><span>2</span><p>Ek revizyonun ücreti ne olacak?</p></div>
            <div class="radar-item"><span>3</span><p>Müşteri içerikleri geç verirse teslim uzar mı?</p></div>
            <div class="note-box">Öneri — taraflar tarafından kontrol edilmelidir.</div>
        </div>
    </div>
</section>

<section class="cta-band">
    <div class="container cta-inner">
        <div><span class="eyebrow light">Sana güvenmiyorum diye değil</span><h2>İkimiz de unutmayalım diye.</h2></div>
        <a class="btn btn-light btn-lg" href="<?= e($loggedIn ? page_url('create') : page_url('register')) ?>">İlk SözFişi’ni oluştur</a>
    </div>
</section>
