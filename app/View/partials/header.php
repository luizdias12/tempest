<header class="site-header">
    <button class="header-toggle-btn" id="headerToggleBtn" aria-label="Abrir ou fechar menu"><i class="fa-solid fa-bars"></i></button>
    <span class="header-title"><?= htmlspecialchars($title ?? '') ?></span>
    <span class="header-server-badge"><i class="fa-solid fa-network-wired"></i> <?= htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? '') ?></span>
    <?php if (!empty($modoAurora)): ?>
        <div class="header-pisca" aria-hidden="true">
            <?php for ($i = 0; $i < 36; $i++): ?><i class="lampada" style="--i:<?= $i ?>"></i><?php endfor; ?>
        </div>
    <?php endif; ?>
</header>
