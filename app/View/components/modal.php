<div id="<?= htmlspecialchars($id ?? 'modal') ?>" class="modal">
    <div class="modal-content <?= htmlspecialchars($size ?? '') ?>">
        <div class="modal-header">
            <h2><?= $title ?? '' ?></h2>
            <span class="close" data-close>&times;</span>
        </div>
        <div class="modal-body"><?= $content ?? '' ?></div>
        <?php if (!empty($footer)): ?>
            <div class="modal-footer"><?= $footer ?></div>
        <?php endif; ?>
    </div>
</div>
