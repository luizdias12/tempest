<?php

use App\Service\AuthService;

$user = AuthService::getUser();
$isSuporte = AuthService::hasPermission('ti');

?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <span class="sidebar-brand">Intranet</span>
        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Abrir ou fechar menu"><i class="fa-solid fa-bars"></i></button>
    </div>
    <div class="sidebar-user">
        <?php if ($user): ?>
            <div class="sidebar-user-info">
                <div class="sidebar-user-avatar">
                    <i class="fa-solid fa-circle-user"></i>
                </div>
                <div class="sidebar-user-details">
                    <span class="sidebar-user-name"><?= htmlspecialchars($user['name']) ?></span>
                    <span class="sidebar-user-badge"><?= $isSuporte ? '<i class="fa-solid fa-user-shield"></i> Suporte' : '<i class="fa-solid fa-user"></i> Usuário' ?></span>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <nav class="sidebar-nav">
        <a href="/"><i class="fa-solid fa-house"></i> Início</a>

        <?php if ($user): ?>
        <div class="sidebar-area-protegida">
            <a href="/helpdesk/index"><i class="fa-solid fa-desktop"></i> Helpdesk</a>
            <a href="/funcionarios/index"><i class="fa-solid fa-users"></i> Funcionários</a>
            <div class="sidebar-dropdown">
                <a href="#" class="sidebar-dropdown-toggle">
                    <span class="sidebar-dropdown-label"><i class="fa-solid fa-computer"></i> TI</span>
                    <i class="fa-solid fa-chevron-right sidebar-dropdown-chevron"></i>
                </a>
                <div class="sidebar-dropdown-menu">
                    <a href="/ti/lista"><i class="fa-solid fa-list"></i> Lista TI</a>
                </div>
            </div>
            <div class="sidebar-dropdown">
                <a href="#" class="sidebar-dropdown-toggle">
                    <span class="sidebar-dropdown-label"><i class="fa-solid fa-wallet"></i> Financeiro</span>
                    <i class="fa-solid fa-chevron-right sidebar-dropdown-chevron"></i>
                </a>
                <div class="sidebar-dropdown-menu">
                    <a href="/financ/holerite"><i class="fa-solid fa-money-bill"></i> Holerite</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <a href="https://outlook.office.com/" target="_blank"><i class="fa-solid fa-mail-bulk"></i> Outlook Mail</a>
        <a href="http://www.villefort.com.br/" target="_blank"><i class="fa-brands fa-chrome"></i> Villefort</a>
        <a href="https://sistema.villefort.com.br:5000/login.html" target="_blank"><i class="fa-solid fa-cloud"></i> Consinco Web</a>
        <a href="http://villesys.villefort.com.br" target="_blank"><i class="fa-solid fa-laptop-code"></i> VilleSys</a>
        <a href="https://instagram.com/villefortatacarejo" target="_blank"><i class="fa-brands fa-instagram"></i> @villefortatacarejo</a>
        <a href="https://www.villefortentrega.com.br/" target="_blank"><i class="fa-solid fa-shopping-cart"></i> Villefort Entrega</a>

    </nav>
    <div class="sidebar-footer">
        <?php if ($user): ?>
            <a href="/logout" class="sidebar-logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sair</a>
        <?php else: ?>
            <a href="/login" class="sidebar-login"><i class="fa-solid fa-arrow-right-to-bracket"></i> Entrar</a>
        <?php endif; ?>
    </div>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay"></div>