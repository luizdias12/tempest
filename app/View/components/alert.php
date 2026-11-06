<div class="toast-container">
    <?php foreach ($alerts as $alert): ?>
        <div class="toast toast-<?= htmlspecialchars($alert['type']) ?>">
            <span class="toast-message"><?= htmlspecialchars($alert['message']) ?></span>
            <button class="toast-close">&times;</button>
        </div>
    <?php endforeach; ?>
</div>