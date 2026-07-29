<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <span class="sidebar-brand">嵐</span>
        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Abrir ou fechar menu">&#9776;</button>
    </div>
    <nav class="sidebar-nav">
        <a href="/">Início</a>
        <a href="/funcionarios/index">Funcionários</a>
        <div class="sidebar-dropdown">
            <a href="#" class="sidebar-dropdown-toggle">TI</a>
            <div class="sidebar-dropdown-menu">
                <a href="/ti/lista">Lista TI</a>
            </div>
        </div>
    </nav>
    <div class="sidebar-footer">
        <?php
        use App\Service\AuthService;
        $user = AuthService::getUser();
        if ($user): ?>
            <span class="sidebar-user-name"><?= htmlspecialchars($user['name']) ?></span>
            <a href="/logout" class="sidebar-logout">Sair</a>
        <?php else: ?>
            <a href="/login" class="sidebar-login">Entrar</a>
        <?php endif; ?>
    </div>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
