<header class="site-header">
    <button class="header-toggle-btn" id="headerToggleBtn" aria-label="Abrir ou fechar menu"><i class="fa-solid fa-bars"></i></button>
    <span class="header-title"><?= htmlspecialchars($title ?? '嵐') ?></span>
    <span class="header-server-badge"><i class="fa-solid fa-network-wired"></i> <?= htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? '') ?></span>
</header>
