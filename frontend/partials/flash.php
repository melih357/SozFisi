<?php foreach (pull_flashes() as $flash): ?>
<div class="container flash-wrap">
    <div class="alert alert-<?= e($flash['type']) ?>" role="alert">
        <span><?= e($flash['message']) ?></span>
        <button type="button" aria-label="Kapat" data-alert-close>×</button>
    </div>
</div>
<?php endforeach; ?>
