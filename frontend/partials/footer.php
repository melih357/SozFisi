</main>
<?php if (!$printMode): ?>
<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <img class="footer-logo" src="<?= e(url('frontend/assets/images/logo-horizontal.png')) ?>" alt="SözFişi">
            <p>İki tarafın aynı şeyi gördüğünden emin olan ortak hafıza.</p>
        </div>
        <div>
            <strong>Ürün</strong>
            <a href="<?= e(page_url('home')) ?>#nasil-calisir">Nasıl çalışır?</a>
            <a href="<?= e(page_url('create')) ?>">Yeni SözFişi</a>
        </div>
        <div>
            <strong>Güven</strong>
            <a href="<?= e(page_url('privacy')) ?>">Gizlilik</a>
            <a href="<?= e(page_url('terms')) ?>">Kullanım koşulları</a>
        </div>
    </div>
    <div class="container footer-bottom">© <?= date('Y') ?> SözFişi · Konuştuk. Netleştirdik.</div>
</footer>
<script src="<?= e(url('frontend/assets/js/app.js')) ?>" defer></script>
<?php if (($page ?? '') === 'create'): ?>
<script src="<?= e(url('frontend/assets/js/create.js')) ?>" defer></script>
<?php endif; ?>
<?php endif; ?>
</body>
</html>
