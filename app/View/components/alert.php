<?php
    $type = $type ?? 'info';
    $message = $message ?? '';
?>

<div class="alert alert-<?= htmlspecialchars($type) ?>">
    <?= htmlspecialchars($message) ?>
</div>