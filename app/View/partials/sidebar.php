<?php

use App\Service\AuthService;
$user = AuthService::getUser();

?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <span class="sidebar-brand">Intranet</span>
        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Abrir ou fechar menu"><i data-lucide="menu"></i></button>
    </div>
    <div class="sidebar-user">
        <?php if ($user): ?>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name"><i data-lucide="circle-user-round"></i>　<?= htmlspecialchars($user['name']) ?></span>
            </div>
        <?php endif; ?>
    </div>
    <nav class="sidebar-nav">
        <a href="/"><i data-lucide="house"></i> Início</a>
        <a href="/helpdesk/index"><i data-lucide="hand-helping"></i> Helpdesk</a>
        <a href="/funcionarios/index"><i data-lucide="users"></i> Funcionários</a>
        <div class="sidebar-dropdown">
            <a href="#" class="sidebar-dropdown-toggle">
                <span class="sidebar-dropdown-label"><i data-lucide="computer"></i> TI</span>
                <i data-lucide="chevron-right" class="sidebar-dropdown-chevron"></i>
            </a>
            <div class="sidebar-dropdown-menu">
                <a href="/ti/lista"><i data-lucide="list"></i> Lista TI</a>
            </div>
        </div>
        <div class="sidebar-dropdown">
            <a href="#" class="sidebar-dropdown-toggle">
                <span class="sidebar-dropdown-label"><i data-lucide="wallet"></i> Financeiro</span>
                <i data-lucide="chevron-right" class="sidebar-dropdown-chevron"></i>
            </a>
            <div class="sidebar-dropdown-menu">
                <a href="/financ/holerite"><i data-lucide="file-text"></i> Holerite</a>
            </div>
        </div>
    </nav>
    <div class="sidebar-footer">
        <?php if ($user): ?>
            <a href="/logout" class="sidebar-logout"><i data-lucide="log-out"></i> Sair</a>
        <?php else: ?>
            <a href="/login" class="sidebar-login"><i data-lucide="log-in"></i> Entrar</a>
        <?php endif; ?>
    </div>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay"></div>